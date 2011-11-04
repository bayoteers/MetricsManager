#!/usr/bin/perl
use POSIX qw(strftime);

#==========================================================================
# Bugzilla statistics: fetch_statistics_from_bugzilla.pl
#
# Copyright 2011, Nokia Oy
# Maintainer: Grzegorz Szura <ext-grzegorz.szura@nokia.com>
# Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php
#
# Date: Thu Feb  3 14:21:00 EET 2011
#==========================================================================

# 1/ read configuratio
# 2/ for each monitored product
#   a/ fetch new snapshot - request ot Bugzilla
#   b/ compare just fetched snapshot with previous one - prepare data for printing them on the web page (daily view)
#   b/ compare just fetched snapshot with previous one - prepare data for printing them on the web page (weekly view)
#   c/ if requested, send e-mail with product's statistics to product owner
# 3/ if requested, send e-mail with statistics for all products (statistics from last day only)
# 4/ prepare summary statistics file for all monitored products

$help = <<'!END!';
usage: fetch_statistics_from_bugzilla.pl <config file>
	<config file> - config file - full path

This script fetches new snapshot of bugs from Bugzilla and prepares data for printing them on the web page.

!END!


if ($#ARGV != 0) {
	print "ERROR: too few or too many argument(s)\n$help";
	exit -1;
}

if ($ARGV[0] eq "-h" || $ARGV[0] eq "--help") {
	print "\n$help";
	exit -1;
}

$CONFIG_FILE=$ARGV[0];
die "Config file does not exists: $CONFIG_FILE - exit." unless (-e $CONFIG_FILE);

$COMMON_PARAMS_FILE = read_config_entry("COMMON_PARAMS_FILE");
$LOG_FILE = read_config_entry("LOG_FILE");
open (LOG, ">>", $LOG_FILE);
info("======================================================================================");
info("START");

$SUBSET_OF = read_config_entry("SUBSET_OF", "can be empty");
$PARENT_STATS_FOLDER = read_config_entry("STATISTICS_BASE_PATH") . "/" . $SUBSET_OF;
if ($SUBSET_OF ne "") {
	if (! -e $PARENT_STATS_FOLDER) {
		fatal("Variable 'SUBSET_OF' is defined, but I cannot find parent statistic folder: '$PARENT_STATS_FOLDER'");
	}
}
$BUGZILLA_URL_BASE = read_config_entry("BUGZILLA_URL_BASE");
$BUGZILLA_SNAPSHOT_COLUMN_LIST = ",bug_severity,priority,short_desc,";
$SUBGROUPS_COLUMN_NAME = read_config_entry("SUBGROUPS_COLUMN_NAME", "can be empty");
if ($SUBGROUPS_COLUMN_NAME ne "") {
	$BUGZILLA_SNAPSHOT_COLUMN_LIST .= "$SUBGROUPS_COLUMN_NAME,";
}
$WGET_EXTRA_PARAMETERS = read_config_entry("WGET_EXTRA_PARAMETERS", "can be empty");

# variables needed to create links in GUI
@BUGS_OPEN = read_config_rule(read_config_entry("BUGS_OPEN"), 1);
@BUGS_FIXED = read_config_rule(read_config_entry("BUGS_FIXED"), 1);
@BUGS_RELEASED = read_config_rule(read_config_entry("BUGS_RELEASED"), 1);
@BUGS_CLOSED = read_config_rule(read_config_entry("BUGS_CLOSED"), 1);
@BUGS_RELEASEABLE = read_config_rule(read_config_entry("BUGS_RELEASEABLE"), 1);
@BUGS_NOT_CONFIRMED = read_config_rule(read_config_entry("BUGS_NOT_CONFIRMED"), 1);

# variables needed to fetch the snapshot from BZ
$BUGZILLA_URL_SNAPSHOT = "buglist.cgi?query_format=advanced&ctype=csv&columnlist=$BUGZILLA_SNAPSHOT_COLUMN_LIST";
$BUGZILLA_URL_COMMON_PARAMS = (create_bugzilla_params_from_rule( read_config_rule( read_config_entry("BUGZILLA_URL_COMMON_PARAMS"), 0 ) ))[0];


# other
$STATS_URL_BASE = read_config_entry("STATS_URL_BASE");
$STATISTICS = read_config_entry("STATISTICS");
$STATS_FOLDER = read_config_entry("STATISTICS_BASE_PATH") . "/" . read_config_entry("STATISTICS");
$TMP_DIR = read_config_entry("TMP_DIR");
$PRODUCTS_CONFIG_FILE = read_config_entry("PRODUCTS_CONFIG_FILE");
die "File with list of products does not exists: $PRODUCTS_CONFIG_FILE - exit." unless (-e $PRODUCTS_CONFIG_FILE);
$MAIL_TO = read_config_entry("MAIL_TO", "can be empty");
$ADMIN_MAIL = read_config_entry("ADMIN_MAIL");
$SENT_EMAIL_NOTIFICATION = read_config_entry("SENT_EMAIL_NOTIFICATION");
$INCOMPLETE_CLASSIFICATION = read_config_entry("INCOMPLETE_CLASSIFICATION", "can be empty");

if (! -e $TMP_DIR) {
	if (!mkdir $TMP_DIR) {
		fatal("Cannot create temporary folder '$TMP_DIR': $!");
	}
}
if (! -e $STATS_FOLDER) {
	if (!mkdir $STATS_FOLDER) {
		fatal("Cannot create statistics' folder '$STATS_FOLDER': $!");
	}
}

# 'Static' variables - it's better to not touch them
$VARIABLES_FILE_NAME = "variables.php";
$RAW_DATA_DIR = "raw_data";
$ALL_PRODUCTS_DIR = "all";
$DAILY_STATS_HISTORY_FILE_NAME = "daily_stats";
$WEEKLY_STATS_HISTORY_FILE_NAME = "weekly_stats";



chomp($time = `date +"%Y-%m-%d_%H-%M"`);
chomp($time_email = `date +"%Y-%m-%d %H:%M"`);
chomp($week_day = `date +"W%V, %A"`);
$snapshot_output_file = "$time.csv";
if ($SUBSET_OF ne "") {
	$snapshot_output_file = "";
}

$week_day = "XX";
$changes_outfile_day = "";
$changes_outfile_week = "";
$errors = "";

%PRODUCTS = {};
$PRODUCTS_LIST = "";
read_products_list();

# bugs which cannot be verified becasue of open dependencies
$BUGS_WHICH_CANNOT_BE_VERIFIED = "";
$BUGS_DEPENDS_ON_DEPENDENCIES_STR = read_config_entry("BUGS_DEPENDS_ON_DEPENDENCIES", "can be empty");
%columns_array = ();

if ($SUBSET_OF eq "" && $BUGS_DEPENDS_ON_DEPENDENCIES_STR ne "") {
	# variables needed to check dependencies of bugs to check if they can be verified
	@BUGS_DEPENDS_ON_DEPENDENCIES = read_config_rule($BUGS_DEPENDS_ON_DEPENDENCIES_STR, 0);
	@BUGZILLA_URL_BUGS_WITH_DEPENDENCIES_EXT = create_bugzilla_params_from_rule(@BUGS_DEPENDS_ON_DEPENDENCIES);
	$BUGZILLA_URL_BUGS_WITH_DEPENDENCIES_BASE = "buglist.cgi?ctype=csv&field0-0-0=dependson&type0-0-0=regexp&value0-0-0=.%2B" . create_bugzilla_param_column_list(@BUGS_DEPENDS_ON_DEPENDENCIES);

	# fetch the list of resolved bugs with dependencies from Bugzilla
	$do_continue = 1;
	$file = "$TMP_DIR/bugs_with_dependencies_file";
	$file_part = "$TMP_DIR/bugs_with_dependencies_file_part";
	execute_command("echo '' > $file");
	for $ext ( @BUGZILLA_URL_BUGS_WITH_DEPENDENCIES_EXT ) {
		if ($ext ne "") {
			$bugzilla_bugs_with_dependencies_url = $BUGZILLA_URL_BASE . $BUGZILLA_URL_BUGS_WITH_DEPENDENCIES_BASE . $ext . $BUGZILLA_URL_COMMON_PARAMS;
			info("Get the list of resolved bugs with dependencies, save it in $file_part, next append to $file");
			execute_command("wget $WGET_EXTRA_PARAMETERS -O $file_part \"$bugzilla_bugs_with_dependencies_url\"");
			execute_command("cat $file_part >> $file");
			execute_command("echo '' >> $file");
		}
	}

	# check if snapshot has been fetched
	if (! -e "$file") {
		$errors .= "Cannot find file '$file' - the list of resolved bugs with dependencies has not been fetched from Bugzilla..\n";
		$do_continue = 0;
	}
	# check if file is not empty - there should be at least header line
	if (-z "$file") {
		$errors .= "File is empty '$file' - the list of resolved bugs with dependencies has not been fetched correctly from Bugzilla.. - removing the file\n";
		$do_continue = 0;
	}

	if ($do_continue == 1) {
		@columns_list = get_column_list(@BUGS_DEPENDS_ON_DEPENDENCIES);
		unshift(@columns_list, "bug_id");
		%BUG = ();
		for $column_name ( @columns_list ) {
			if ($column_name ne "") {
				$BUG{$column_name} = "";
			}
		}
		$dependencies_list = "";
		execute_command("echo '\n' >> $file");
		open(FILE1, "<", $file);
		while ( chomp($a = <FILE1>) ) {
			if ( $a =~ /\#/ || $a eq "" ) {
				next;
			}
			$a =~ s/"//g;
			@arr = split(",", $a);
			if ( $arr[0] eq "bug_id") {
				next;
			}
			for $column_name ( @columns_list ) {
				if ($column_name ne "") {
					$BUG{$column_name} = shift(@arr);
				}
			}
			$dependencies_list .= $BUG{"bug_id"} . ",";
		}
		close FILE1;
		$BUGS_WHICH_CANNOT_BE_VERIFIED = check_bugs_dependencies( $dependencies_list );
	}
}

# ==================================================

for $product ( sort (keys %PRODUCTS) )
{
	$product_name = $product;
	$product_name =~ s/_/ /g;

	# prepare folder for product's data
	$snapshot_output_dir = "$STATS_FOLDER/$product";
	if ($SUBSET_OF ne "") {
		# SYMBOLIC LINK
		$parent_snapshot_output_dir = "$PARENT_STATS_FOLDER/$product";
		if (! -e $parent_snapshot_output_dir) {
			fatal("Variable 'SUBSET_OF' is defined, but I cannot find parent statistic folder for product: '$parent_snapshot_output_dir':");
		}
		if (-e $snapshot_output_dir) {
			if (! -l $snapshot_output_dir) {
				fatal("Variable 'SUBSET_OF' is defined, but a product folder '$snapshot_output_dir' is not a symbolic link of parent product folder: '$parent_snapshot_output_dir':");
			}
			# TODO - test this!!
			if ( readlink($snapshot_output_dir) ne $parent_snapshot_output_dir ) {
				info("Variable 'SUBSET_OF' is defined, but a symbolic link is not pointing to the parent product folder '$parent_snapshot_output_dir'. Current situation: '$snapshot_output_dir' -> '" . readlink($snapshot_output_dir) . "' ");
				execute_command("rm -f '$snapshot_output_dir'");
				# create a symbolic link
				if (symlink($parent_snapshot_output_dir, $snapshot_output_dir) == 1 ) {
					info("Symbolic link created: '$snapshot_output_dir' -> '$parent_snapshot_output_dir'");
				} else {
					fatal("Cannot create symbolic link of parent folder '$snapshot_output_dir' -> '$parent_snapshot_output_dir': $!");
				}
			}
		} else {
			# create a symbolic link
			if (symlink($parent_snapshot_output_dir, $snapshot_output_dir) == 1 ) {
				info("Symbolic link created: '$snapshot_output_dir' -> '$parent_snapshot_output_dir'");
			} else {
				fatal("Cannot create symbolic link of parent folder '$snapshot_output_dir' -> '$parent_snapshot_output_dir': $!");
			}
		}
		
	} else {
		# REAL FOLDER
		if (! -e $snapshot_output_dir) {
			if (!mkdir $snapshot_output_dir) {
				fatal("Cannot create folder '$snapshot_output_dir': $!");
			}
		}
	}
	
	# create '$VARIABLES_FILE_NAME' file

	open(OUTFILE, ">", "$snapshot_output_dir/$VARIABLES_FILE_NAME");
	print OUTFILE "<?php\n";
	print OUTFILE "\$BUGZILLA_URL_BASE = '$BUGZILLA_URL_BASE';\n";
	print OUTFILE "\$BUGZILLA_URL_COMMON = '$BUGZILLA_URL_COMMON_PARAMS" . $PRODUCTS{$product} . "';\n";
	print OUTFILE "\$BUGS_ACTIVE = '" . (create_bugzilla_params_from_rule(@BUGS_OPEN))[0] . (create_bugzilla_params_from_rule(@BUGS_FIXED))[0] . (create_bugzilla_params_from_rule(@BUGS_RELEASED))[0]	. "';\n";
	print OUTFILE "\$BUGS_NOT_RELEASED = '" . (create_bugzilla_params_from_rule(@BUGS_FIXED))[0] . (create_bugzilla_params_from_rule(@BUGS_RELEASEABLE))[0] . "';\n";
	print OUTFILE "\$BUGS_OPEN = '" . (create_bugzilla_params_from_rule(@BUGS_OPEN))[0] . "';\n";
	print OUTFILE "\$BUGS_NOT_CONFIRMED = '" . (create_bugzilla_params_from_rule(@BUGS_OPEN))[0] . (create_bugzilla_params_from_rule(@BUGS_NOT_CONFIRMED))[0] . "';\n";
	print OUTFILE "?>";
	close (OUTFILE);

	$snapshot_output_dir .= "/$RAW_DATA_DIR";
	
	# prepare folder for snapshot
	if ($SUBSET_OF ne "")
	{
		# SYMBOLIC LINK - this statistics are a subset '$SUBSET_OF' statistics so we assume that snapshot from Bugzilla is already fetched
		# TODO - implement this - check if raw file exists
		
		if (! -e $snapshot_output_dir) {
			fatal("Variable 'SUBSET_OF' is defined, but I cannot find the RAW_DATA dir for product: '$snapshot_output_dir':");
		}
	}
	else
	{
		# REAL FOLDER - fetch statistics from Bugzilla
		if (! -e $snapshot_output_dir) {
			if (!mkdir $snapshot_output_dir) {
				fatal("Cannot create folder '$snapshot_output_dir': $!");
			}
		}

		# fetch new snapshot from Bugzilla
		$bugzilla_request = $BUGZILLA_URL_BASE . $BUGZILLA_URL_SNAPSHOT . $BUGZILLA_URL_COMMON_PARAMS . $PRODUCTS{$product};
		$bugzilla_request_active = $bugzilla_request . (create_bugzilla_params_from_rule(@BUGS_OPEN))[0] . (create_bugzilla_params_from_rule(@BUGS_FIXED))[0] . (create_bugzilla_params_from_rule(@BUGS_RELEASED))[0];
		# TODO - 'bug_status' cannot be hardcoded in $bugzilla_request_closed
		# this is a workaround
		$bugzilla_request_closed = $bugzilla_request;
		if ( create_bugzilla_param_column_list(@BUGS_CLOSED) eq "&columnlist=,bug_status,") {
			$bugzilla_request_closed .= "&chfield=bug_status&chfieldfrom=5d&chfieldto=Now" . (create_bugzilla_params_from_rule(@BUGS_CLOSED))[0];
		} else {
			$bugzilla_request_closed .= (create_bugzilla_params_from_rule(@BUGS_CLOSED))[0];
		}
		info("Get snapshot for $product, save it in $snapshot_output_dir/$snapshot_output_file");
		execute_command("wget $WGET_EXTRA_PARAMETERS -O $snapshot_output_dir/$snapshot_output_file \"$bugzilla_request_active\"");
		execute_command("echo >> $snapshot_output_dir/$snapshot_output_file");
		execute_command("wget $WGET_EXTRA_PARAMETERS -O $TMP_DIR/$snapshot_output_file \"$bugzilla_request_closed\"");
		execute_command("cat $TMP_DIR/$snapshot_output_file >> $snapshot_output_dir/$snapshot_output_file");
		execute_command("rm $TMP_DIR/$snapshot_output_file");
		
		$do_continue = 1;

		# check if snapshot has been fetched
		if (! -e "$snapshot_output_dir/$snapshot_output_file") {
			$errors .= "Cannot find file '$snapshot_output_dir/$snapshot_output_file' - statistics has not been fetched from Bugzilla..\n";
			$do_continue = 0;
		}
		# check if file is not empty - there should be at least header line
		if (-z "$snapshot_output_dir/$snapshot_output_file") {
			$errors .= "Statistics file is empty '$snapshot_output_dir/$snapshot_output_file' - statistics has not been fetched correctly from Bugzilla.. - removing the file\n";
			execute_command("rm -f '$snapshot_output_dir/$snapshot_output_file'");
			$do_continue = 0;
		}
	

		if ($do_continue == 1) {
			# find the previous snapshot files (previous day for daily stats and last Monday for weekly stats
			$prev_snapshot_daily = "none";
			$prev_snapshot_weekly = "none";
			opendir(S_DIR, $snapshot_output_dir);
			foreach my $s ( sort {$b cmp $a} (readdir S_DIR ) )
			{
				if (-d "$snapshot_output_dir/$s" || $s eq $snapshot_output_file || $s eq ".." || $s eq ".") {
					next;
				}
				if ($snapshot_output_file eq "") {
					# read todays snapshot file -> this part of code should be executed only when ($SUBSET_OF ne "")
					$snapshot_output_file = $s;
				} else {
					# previous daily snapshot (previous day)
					if ($prev_snapshot_daily eq "none") {
						$prev_snapshot_daily = $s;
					}
					# previous weekly snapshot (last Monday)
					($year, $month, $day, $hour, $minute) = ($s =~ /(\d+)-(\d+)-(\d+)_(\d+)-(\d+)\.csv/);
					$dow = strftime "%u", (0, $minute, $hour, $day, $month-1, $year-1900);
					if ($dow == 1) {
						$prev_snapshot_weekly = $s;
						last;
					}
				}
			}
			closedir S_DIR;

			# compare just fetched snapshot with previous one - prepare data for printing them on the web page (daily view)
			info("compare just fetched snapshot with previous one - prepare data for printing them on the web page");
			info("daily view: $product: $prev_snapshot_daily <-> $snapshot_output_file");
			$ret = execute_command("/home/btests-www/bin/compare_statistics_snapshots.pl -d $CONFIG_FILE $product $prev_snapshot_daily $snapshot_output_file $BUGS_WHICH_CANNOT_BE_VERIFIED");
			if ($ret =~ /ERROR/) {
				# TODO - if the file does not contain the header line then wget failed and file should be removed... shoud be..?
				$errors .= $ret . "\n";
			}
		
			my @ret_c = split(";", $ret);
			$week_day = $ret_c[0];
			$changes_outfile_day = $ret_c[1];

			# compare just fetched snapshot with previous one - prepare data for printing them on the web page (weekly view)
			info("weekly view: $product: $prev_snapshot_weekly <-> $snapshot_output_file");
			$ret = execute_command("/home/btests-www/bin/compare_statistics_snapshots.pl -w $CONFIG_FILE $product $prev_snapshot_weekly $snapshot_output_file $BUGS_WHICH_CANNOT_BE_VERIFIED");
			if ($ret =~ /ERROR/) {
				$errors .= $ret . "\n";
			}
			my @ret_c = split(";", $ret);
			$changes_outfile_week = $ret_c[1];
		}
	}
}

# create '$VARIABLES_FILE_NAME' file for all monitored products
if (! -e "$STATS_FOLDER/$ALL_PRODUCTS_DIR") {
	if (!mkdir "$STATS_FOLDER/$ALL_PRODUCTS_DIR") {
		fatal("Cannot create folder '$STATS_FOLDER/$ALL_PRODUCTS_DIR': $!");
	}
}
open(OUTFILE, ">", "$STATS_FOLDER/$ALL_PRODUCTS_DIR/$VARIABLES_FILE_NAME");
print OUTFILE "<?php\n";
print OUTFILE "\$BUGZILLA_URL_BASE = '$BUGZILLA_URL_BASE';\n";
if ($SUBSET_OF eq "" && $INCOMPLETE_CLASSIFICATION ne "true") {
	print OUTFILE "\$BUGZILLA_URL_COMMON = '$BUGZILLA_URL_COMMON_PARAMS';\n";
} else {
	print OUTFILE "\$BUGZILLA_URL_COMMON = '$BUGZILLA_URL_COMMON_PARAMS$PRODUCTS_LIST';\n";
}
print OUTFILE "\$BUGS_ACTIVE = '" . (create_bugzilla_params_from_rule(@BUGS_OPEN))[0] . (create_bugzilla_params_from_rule(@BUGS_FIXED))[0] . (create_bugzilla_params_from_rule(@BUGS_RELEASED))[0]	. "';\n";
print OUTFILE "\$BUGS_NOT_RELEASED = '" . (create_bugzilla_params_from_rule(@BUGS_FIXED))[0] . (create_bugzilla_params_from_rule(@BUGS_RELEASEABLE))[0] . "';\n";
print OUTFILE "\$BUGS_OPEN = '" . (create_bugzilla_params_from_rule(@BUGS_OPEN))[0] . "';\n";
print OUTFILE "\$BUGS_NOT_CONFIRMED = '" . (create_bugzilla_params_from_rule(@BUGS_OPEN))[0] . (create_bugzilla_params_from_rule(@BUGS_NOT_CONFIRMED))[0] . "';\n";
print OUTFILE "?>";
close (OUTFILE);

if ($SUBSET_OF ne "")
{
	# read $changes_outfile_day $changes_outfile_week variables, $week_day
	open(STATSFILE, "<", "$PARENT_STATS_FOLDER/$ALL_PRODUCTS_DIR/$DAILY_STATS_HISTORY_FILE_NAME") || fatal("can't open a file for reading: $PARENT_STATS_FOLDER/$ALL_PRODUCTS_DIR/$DAILY_STATS_HISTORY_FILE_NAME");
	while ( $a = <STATSFILE> )
	{
		my @columns = split(";;;", $a);
		$week_day = $columns[0];
		$changes_outfile_day = $columns[2];
	}
	close STATSFILE;

	open(STATSFILE, "<", "$PARENT_STATS_FOLDER/$ALL_PRODUCTS_DIR/$WEEKLY_STATS_HISTORY_FILE_NAME") || fatal("can't open a file for reading: $PARENT_STATS_FOLDER/$ALL_PRODUCTS_DIR/$WEEKLY_STATS_HISTORY_FILE_NAME");
	while ( $a = <STATSFILE> )
	{
		my @columns = split(";;;", $a);
		$changes_outfile_week = $columns[2];
	}
	close STATSFILE;
}

# prepare summary statistics file for all monitored products
info("prepare summary statistics for all monitored products");
$ret = execute_command("/home/btests-www/bin/update_all_products_statistics.pl -d $CONFIG_FILE $changes_outfile_day");
if ($ret =~ /ERROR/) {
	$errors .= $ret . "\n";
}
$ret = execute_command("/home/btests-www/bin/update_all_products_statistics.pl -w $CONFIG_FILE $changes_outfile_week");
if ($ret =~ /ERROR/) {
	$errors .= $ret . "\n";
}

# create global variables.php file
$GLOBAL_VARIABLES = "";
$GLOBAL_VARIABLES .= read_global_variable("STR_START_PAGE");
$GLOBAL_VARIABLES .= read_global_variable("STR_ALL_PRODUCTS");
$GLOBAL_VARIABLES .= read_global_variable("STR_GRAPHS_FOR_ALL_PRODUCTS");
$GLOBAL_VARIABLES .= read_global_variable("STR_PRODUCT");
$GLOBAL_VARIABLES .= read_global_variable("STR_COMPONENT");
$GLOBAL_VARIABLES .= read_global_variable("STR_ACTIVE");
$GLOBAL_VARIABLES .= read_global_variable("STR_UNVERIFIED");
$GLOBAL_VARIABLES .= read_global_variable("STR_UNRELEASED");
$GLOBAL_VARIABLES .= read_global_variable("STR_OPEN");
$GLOBAL_VARIABLES .= read_global_variable("STR_UNCONFIRMED");
$GLOBAL_VARIABLES .= read_global_variable("STR_INFLOW");
$GLOBAL_VARIABLES .= read_global_variable("STR_NEW");
$GLOBAL_VARIABLES .= read_global_variable("STR_REOPENED");
$GLOBAL_VARIABLES .= read_global_variable("STR_OUTFLOW");
$GLOBAL_VARIABLES .= read_global_variable("STR_RESOLVED");
$GLOBAL_VARIABLES .= read_global_variable("STR_MOVED_OUT");
$GLOBAL_VARIABLES .= read_global_variable("STR_RELEASED");
$GLOBAL_VARIABLES .= read_global_variable("STR_CLOSED");
$GLOBAL_VARIABLES .= read_global_variable("STR_ACTIVE_DESC");
$GLOBAL_VARIABLES .= read_global_variable("STR_UNVERIFIED_DESC");
$GLOBAL_VARIABLES .= read_global_variable("STR_UNRELEASED_DESC");
$GLOBAL_VARIABLES .= read_global_variable("STR_OPEN_DESC");
$GLOBAL_VARIABLES .= read_global_variable("STR_UNCONFIRMED_DESC");
$GLOBAL_VARIABLES .= read_global_variable("STR_NEW_DESC");
$GLOBAL_VARIABLES .= read_global_variable("STR_REOPENED_DESC");
$GLOBAL_VARIABLES .= read_global_variable("STR_RESOLVED_DESC");
$GLOBAL_VARIABLES .= read_global_variable("STR_MOVED_OUT_DESC");
$GLOBAL_VARIABLES .= read_global_variable("STR_RELEASED_DESC");
$GLOBAL_VARIABLES .= read_global_variable("STR_CLOSED_DESC");

if ($GLOBAL_VARIABLES ne "") {
	open(OUTFILE, ">", "$STATS_FOLDER/$VARIABLES_FILE_NAME");
	print OUTFILE "<?php\n$GLOBAL_VARIABLES?>";
	close (OUTFILE);
}


# if requested, send e-mail with information that statistics have been collected
if ($SENT_EMAIL_NOTIFICATION eq "true")
{
	$message = "Statistics '$STATISTICS' have been updated and are available on\r\n"
		. "$STATS_URL_BASE?stats=$STATISTICS\r\n\r\n";
	$subject = "'$STATISTICS' bug statistics updated - $week_day";
	$command = "echo \"$message\" | mutt -s \"$subject\"";
	$command = $command . " -c $ADMIN_MAIL";
	$command = $command . " $MAIL_TO";
	execute_command($command);
}

# check if error occurred while comparing snapshots
if ($errors ne "") {
	fatal ("Errors occurred:\n$errors");
}

info("END");
close LOG;



# ==================================================
sub read_global_variable() {
	$tmp = read_config_entry(@_[0], "can be empty");
	if ($tmp ne "") {
		return "\$" . @_[0] . " = '" . $tmp . "';\n";
	}
}

sub check_bugs_dependencies() {
	# check dependencies of bugs provided in first argument
	# return list of bugs, which dependencies are not closed, i.e. list of bugs, which cannot be verified

	if ( @_[0] eq "") {
		info("List of bugs with dependencies is empty - nothing to do.. exit.");
		return "";
	}

	my %d = ();
	my %b = ();
	my $b_id = "";
	my $l = "";
	my $BUGZILLA_URL_BUGS_WITH_DEPENDENCIES_DETAILS = "show_bug.cgi?ctype=xml&excludefield=attachment&excludefield=long_desc&excludefield=cc&excludefield=short_desc&excludefield=reporter&excludefield=assigned_to&excludefield=qa_contact&excludefield=token&excludefield=group&excludefield=creation_ts&excludefield=delta_ts&excludefield=keywords&excludefield=status_whiteboard&id=";
	my $BUGZILLA_URL_BUG_DEPENDENCIES = "buglist.cgi?query_format=advanced&ctype=csv" . create_bugzilla_param_column_list(@BUGS_CLOSED) . "&bug_id=";
	@columns_list = get_column_list(@BUGS_CLOSED);
	unshift(@columns_list, "bug_id");
	%columns_array = ();
	for $i ( 0 .. $#columns_list ) {
		$columns_array{ $columns_list[$i] } = $i;
	}
	

	# fetch the details of resolved bugs with dependencies from Bugzilla
	$bugzilla_dependencies_details_url = $BUGZILLA_URL_BASE . $BUGZILLA_URL_BUGS_WITH_DEPENDENCIES_DETAILS . @_[0];
	$dependencies_details_file = "$TMP_DIR/dependencies_details_file";
	info("Get the details of resolved bugs with dependencies, save it in $dependencies_details_file");
	execute_command("wget $WGET_EXTRA_PARAMETERS -O $dependencies_details_file \"$bugzilla_dependencies_details_url\"");

	
	open(FILE1, "<", $dependencies_details_file);
	while ( chomp($a = <FILE1>) ) {
		if ( $a =~ /<bug_id>(\d+)<\/bug_id>/ ) {
			$b_id = $1;
		} elsif ( $a =~ /<dependson>(\d+)<\/dependson>/ ) {
			$l .= "$1,";
			if (exists $b{$b_id}) {
				$b{$b_id} .= ",$1";
			} else {
				$b{$b_id} = $1;
			}
			$d{$1} = $b_id;
		} else {
			next;
		}
	}
	close FILE1;
	
	# fetch the list of resolved bugs with dependencies from Bugzilla
	$bugzilla_bugs_dependencies_url = $BUGZILLA_URL_BASE . $BUGZILLA_URL_BUG_DEPENDENCIES . $l;
	$file = "$TMP_DIR/dependencies_file";
	info("Get the list of bugs blocking other resolved bugs, save it in $file");
	execute_command("wget $WGET_EXTRA_PARAMETERS -O $file \"$bugzilla_bugs_dependencies_url\"");
	
	$do_continue = 1;

	# check if snapshot has been fetched
	if (! -e "$file") {
		$errors .= "Cannot find file '$file' - the list of bugs blocking other resolved bugs has not been fetched from Bugzilla..\n";
		$do_continue = 0;
	}
	# check if file is not empty - there should be at least header line
	if (-z "$file") {
		$errors .= "Statistics file is empty '$file' - the list of bugs blocking other resolved bugs has not been fetched from Bugzilla..  - removing the file\n";
		$do_continue = 0;
	}

	if ($do_continue == 1) {
		execute_command("echo '\n' >> $file");
		open(FILE1, "<", $file);
		while ( chomp($a = <FILE1>) ) {
			if ( $a =~ /\#/ || $a eq "" ) {
				next;
			}
			$a =~ s/"//g;
			@BUG = split(",", $a);
			if ( $BUG[0] eq "bug_id") {
				next;
			}
			
			#if ( $status eq "VERIFIED" || $status eq "CLOSED" ) {
			if ( match_to_the_rule("closed", @BUG) == 1 ) {
				delete $d{$BUG[0]};
			}
		}
		close FILE1;
	}
	$ret = "";
	foreach $b_id ( sort (keys %b) ) {
		$out = 1;
		@db = split(",", $b{$b_id});
		foreach (@db) {
			if ( exists $d{$_} ) {
				$out = 0;
			}
		}
		if ($out == 0) {
			$ret .= "$b_id,";
		}
	}
	return $ret;
}

# ==================================================
sub match_to_the_rule
{
	my ($rule_type, @bug) = @_;
	my @rule;
	
	if ($rule_type eq "closed") {
		@rule = @BUGS_CLOSED;
	} elsif ($rule_type eq "depends_on_dependencies") {
		@rule = @BUGS_DEPENDS_ON_DEPENDENCIES;
	}

	for $i ( 0 .. $#rule ) {
		$matches_and = 1;
		for my $param_name (keys  %{$rule[$i]}) {
			my $param_values = $rule[$i]{$param_name};
			#print "-- $param_name:  $param_values\n===" . $bug[$columns_array{$param_name}] . "\n";
			my $matches_or = 0;
			my @params = split(/,/, $param_values);

			foreach my $param (@params) {
				if ( $param eq $bug[$columns_array{$param_name}] ) {
					#print "$param - " . $bug[$columns_array{$param_name}] . " == maching\n";
					$matches_or = 1;
					last;
				} else {
					#print "$param - " . $bug[$columns_array{$param_name}] . " == NOT maching\n";
				}
			}
			if ($matches_or == 0) {
				$matches_and = 0;
				last;
			}
		}
		if ($matches_and == 1) {
			return 1;
		}
	}
	return 0;
}

# ==================================================
# read parameter from config file:
# - first try to read it from $COMMON_PARAMS_FILE
# - then try to read it from $CONFIG_FILE
# if parameter does not exist in any file and it cannot been empty (the second parameter provided to this method) then report failure
# if parameter exists in both config files, return the one from $CONFIG_FILE
# 
sub read_config_entry {
	if ($COMMON_PARAMS_FILE ne "") {
		chomp($ret_common = execute_command("grep \"^" . @_[0] . " =\" $COMMON_PARAMS_FILE | awk 'BEGIN{FS=\" = \"} {print \$2}'"));
	}
	chomp($ret = execute_command("grep \"^" . @_[0] . " =\" $CONFIG_FILE | awk 'BEGIN{FS=\" = \"} {print \$2}'"));
	if ($ret ne "") {
		return $ret;
	} elsif ($ret_common ne "") {
		return $ret_common;
	} elsif (@_[1] ne "can be empty") {
		fatal("config entry " . @_[0] . " not found or it's value is empty. Please fix config file: $CONFIG_FILE");
	}
	return "";
}

sub read_products_list {
	%PRODUCTS = ();
	open(FILE1, "<", $PRODUCTS_CONFIG_FILE);
	while ( chomp($a = <FILE1>) ) {
		if ( $a =~ /\#/ || $a eq "" ) {
			next;
		}
		($product, $params) = split(";", $a);
		#$PRODUCTS{$product} = $params;
		$PRODUCTS{$product} = (create_bugzilla_params_from_rule( read_config_rule($params) ))[0];
		if ($SUBSET_OF ne "") {
			$PRODUCTS_LIST .= $PRODUCTS{$product};
		}
	}
	close FILE1;
}

sub read_config_rule() {
	my @ret = ();
	if ( $_[0] eq "") {
		return @ret;
	}
	my $i_or = 0;
	my @elements_or = split(/\|\|/, $_[0]);
	foreach my $element_or (@elements_or) {
		my @elements_and = split("&", $element_or);
		foreach my $element_and (@elements_and) {
			my ($param_name, $param_values) = split("=", $element_and);
			$ret[$i_or]{$param_name} = $param_values;
			#my @params = split(/,/, $param_values);
			#$ret[$i_or]{$param_name} = @params;
			if (@_[1] == 1) {
				if ($BUGZILLA_SNAPSHOT_COLUMN_LIST =~ /,$param_name,/) {
				} else {
					$BUGZILLA_SNAPSHOT_COLUMN_LIST .= "$param_name,";
				}
			}
		}
		$i_or++;
	}
	return @ret;
}

sub create_bugzilla_params_from_rule () {
	my @ret = ();
	for $i ( 0 .. $#_ ) {
		for my $param_name (keys  %{$_[$i]}) { 
			my $param_values = $_[$i]{$param_name};
			my @params = split(/,/, $param_values);
			foreach my $param (@params) {
				$ret[$i] .= "&$param_name=$param";
			}
		}
	}
	return @ret;
}

sub get_column_list() {
	my $ret = ",";
	my @columns_list = ();
	for $i ( 0 .. $#_ ) {
		for my $param_name (keys  %{$_[$i]}) { 
			if ($ret =~ /,$param_name,/) {
			} else {
				push(@columns_list, $param_name);
				$ret .= "$param_name,";
			}
		}
	}
	return @columns_list;
}

sub create_bugzilla_param_column_list() {
	my $ret = "&columnlist=,";
	for $i ( 0 .. $#_ ) {
		for my $param_name (keys  %{$_[$i]}) { 
			if ($ret =~ /,$param_name,/) {
			} else {
				$ret .= "$param_name,";
			}
		}
	}
	return $ret;
}


# ==================================================
sub execute_command {
	my $command = @_[0];
	info("COMMAND: $command");
	my $ret = `$command 2<&1`;
	info("RESPONSE: $ret");
	return $ret;
}

sub info {
	if (@_[0] ne "") {
		chomp($time = `date +%Y-%m-%d-%H-%M-%S`);
		print LOG "$time: @_\n";
	}
}

sub fatal
{
	info("FATAL: @_");
	
	# send e-mail to admin that fatal error occurred
	$message = "Fatal error occurred while collecting '$STATISTICS' statistics\r\n\r\nMessage:\r\n@_";
	$subject = "Fatal error";
	$command = "echo \"$message\" | mutt -s \"$subject\" $ADMIN_MAIL";
	execute_command($command);

	close LOG;
	die "@_";
}

# ==================================================


