#!/usr/bin/perl
use POSIX qw(strftime);

#==========================================================================
# BAM (Bugzilla Automated Metrics): create_subset.pl
#
# Copyright 2011, Nokia Oy
# Maintainer: Grzegorz Szura <ext-grzegorz.szura@nokia.com>
# Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php
#
# Date: Thu Feb  3 14:21:00 EET 2011
#==========================================================================

# 1/ read configuration
# if variable 'SUBSET_OF' is defined then check if existing symlink pointing to correct folders.
# if not, then create new ones.

$help = <<'!END!';
usage: create_subset.pl <config file> <start date>
	<config file> - config file - full path
	<start date> - Format: YYYY-MM-DD. Statistic files older than this date will be ignored.

This script creates a new metric from existing one.
The new metric must be a subset of existing metric (SUBSET_OF parameter must be defined).

!END!

if ($#ARGV != 1) {
	print "ERROR: two arguments are needed\n$help";
	exit -1;
}

$CONFIG_FILE=$ARGV[0];
$START_DATE=$ARGV[1];
die "Config file does not exists: $CONFIG_FILE - exit." unless (-e $CONFIG_FILE);

$COMMON_PARAMS_FILE = read_config_entry("COMMON_PARAMS_FILE");
$ADMIN_MAIL = read_config_entry("ADMIN_MAIL");
$LOG_FILE = read_config_entry("LOG_FILE");
open (LOG, ">>", $LOG_FILE);
info("======================================================================================");
info("START");

$STATS_FOLDER = read_config_entry("STATISTICS_BASE_PATH") . "/" . read_config_entry("STATISTICS");
if ( -e $STATS_FOLDER) {
	fatal("Statistic folder already exists: '$STATS_FOLDER'. If you want to create statistics, you have to remove previous one.");
}
if (!mkdir $STATS_FOLDER) {
	fatal("Cannot create statistics' folder '$STATS_FOLDER': $!");
}
$SUBSET_OF = read_config_entry("SUBSET_OF");
$PARENT_STATS_FOLDER = read_config_entry("STATISTICS_BASE_PATH") . "/" . $SUBSET_OF;
if (! -e $PARENT_STATS_FOLDER) {
	fatal("I cannot find the parent statistic folder: '$PARENT_STATS_FOLDER'");
}

$STATS_URL_BASE = read_config_entry("STATS_URL_BASE");
$STATISTICS = read_config_entry("STATISTICS");

$PRODUCTS_CONFIG_FILE = read_config_entry("PRODUCTS_CONFIG_FILE");
die "File with list of products does not exists: $PRODUCTS_CONFIG_FILE - exit." unless (-e $PRODUCTS_CONFIG_FILE);

$TMP_DIR = "/tmp/stats_tmp_" . rand_str(20);
if (-e $TMP_DIR) {
	$TMP_DIR .= rand_str(20);
}
if (!mkdir $TMP_DIR) {
	fatal("Cannot create temporary folder '$TMP_DIR': $!");
}

# 'Static' variables - it's better to not touch them
$VARIABLES_FILE_NAME = "variables.php";
$RAW_DATA_DIR = "raw_data";
$ALL_PRODUCTS_DIR = "all";
$DAILY_STATS_HISTORY_FILE_NAME = "daily_stats";
$WEEKLY_STATS_HISTORY_FILE_NAME = "weekly_stats";
$BUGS_WHICH_CANNOT_BE_VERIFIED_FILE_NAME = "$PARENT_STATS_FOLDER/bugs_with_dependencies";

if ($SUBSET_OF eq "")
{
	# REAL FOLDER - nothing to do - exit
	# We shouldn't reach this point of code ever
	fatal("This statistics is not a subset. Exit..");
}

$snapshot_all_output_dir = "$STATS_FOLDER/$ALL_PRODUCTS_DIR";
if (!mkdir "$snapshot_all_output_dir") {
	fatal("Cannot create folder '$snapshot_all_output_dir': $!");
}
$snapshot_all_output_raw_dir = "$snapshot_all_output_dir/$RAW_DATA_DIR";
if (!mkdir "$snapshot_all_output_raw_dir") {
	fatal("Cannot create folder '$snapshot_all_output_raw_dir': $!");
}


%PRODUCTS = {};
read_products_list();
$products_list = "";
$errors = "";

# ==================================================

for $product ( sort (keys %PRODUCTS) )
{
	# prepare folder for product's data
	$snapshot_output_dir = "$STATS_FOLDER/$product";

	$parent_snapshot_output_dir = "$PARENT_STATS_FOLDER/$product";
	if (! -e $parent_snapshot_output_dir) {
		print "Warning: cannot find parent statistic folder for product: '$parent_snapshot_output_dir'\n";
	}

	if (-e $snapshot_output_dir && ! -l $snapshot_output_dir) {
		fatal("Variable 'SUBSET_OF' is defined, but a product folder '$snapshot_output_dir' is not a symbolic link of parent product folder: '$parent_snapshot_output_dir':");
	}
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

	$snapshot_output_dir .= "/$RAW_DATA_DIR";
	opendir(S_DIR, $snapshot_output_dir);
	foreach my $s ( sort {$a cmp $b} (readdir S_DIR ) )
	{
		if (-d "$snapshot_output_dir/$s" || $s eq ".." || $s eq ".") {
			next;
		}
		#$ymd = ($s =~ /([\d-]+)_[\d-]+\.csv/);
		($year, $month, $day, $hour, $minute) = ($s =~ /(\d+)-(\d+)-(\d+)_(\d+)-(\d+)\.csv/);
		$ymd = "$year-$month-$day";
		if ($START_DATE le $ymd) {
			execute_command("cat $snapshot_output_dir/$s >> $snapshot_all_output_raw_dir/$s");
			execute_command("echo '' >> $snapshot_all_output_raw_dir/$s");
		}
	}	
}


# $snapshot_all_output_dir = "$STATS_FOLDER/$ALL_PRODUCTS_DIR";
# $snapshot_all_output_raw_dir = "$snapshot_all_output_dir/$RAW_DATA_DIR";

# 'ALL PRODUCTS'
# find the previous snapshot files (previous day for daily stats and last Monday for weekly stats
$snapshot_output_file = "";
$prev_snapshot_daily = "none";
$prev_snapshot_weekly = "none";

opendir(S_DIR, $snapshot_all_output_raw_dir);
foreach my $s ( sort {$a cmp $b} (readdir S_DIR ) )
{
	if (-d "$snapshot_all_output_raw_dir/$s" || $s eq ".." || $s eq ".") {
		next;
	}
	if ($snapshot_output_file eq "")
	{
		$snapshot_output_file = $s;
		# compare new snapshot with previous one for ALL PRODUCTS - prepare data for printing them on the web page (daily view)
		info("compare new snapshot with previous one for ALL PRODUCTS - prepare data for printing them on the web page");
		info("daily view: ALL PRODUCTS: $prev_snapshot_daily <-> $snapshot_output_file");
		$ret = execute_command("compare_statistics_snapshots.pl -d $CONFIG_FILE $ALL_PRODUCTS_DIR $prev_snapshot_daily $snapshot_output_file $BUGS_WHICH_CANNOT_BE_VERIFIED_FILE_NAME");
		if ($ret =~ /ERROR/) {
			# TODO - if the file does not contain the header line then wget failed and file should be removed... shoud be..?
			$errors .= $ret . "\n";
		}

		($year, $month, $day, $hour, $minute) = ($s =~ /(\d+)-(\d+)-(\d+)_(\d+)-(\d+)\.csv/);
		$dow = strftime "%u", (0, $minute, $hour, $day, $month-1, $year-1900);
		if ($dow == 1) {
			# compare new snapshot with previous one for ALL PRODUCTS - prepare data for printing them on the web page (weekly view)
			info("weekly view: ALL PRODUCTS: $prev_snapshot_weekly <-> $snapshot_output_file");
			$ret = execute_command("compare_statistics_snapshots.pl -w $CONFIG_FILE $ALL_PRODUCTS_DIR $prev_snapshot_weekly $snapshot_output_file $BUGS_WHICH_CANNOT_BE_VERIFIED_FILE_NAME");
			if ($ret =~ /ERROR/) {
				$errors .= $ret . "\n";
			}
		}
		$prev_snapshot_weekly = $s;
	}
	else
	{
		# previous daily snapshot (previous day)
		$prev_snapshot_daily = $snapshot_output_file;
		$snapshot_output_file = $s;
		# compare new snapshot with previous one for ALL PRODUCTS - prepare data for printing them on the web page (daily view)
		info("compare new snapshot with previous one for ALL PRODUCTS - prepare data for printing them on the web page");
		info("daily view: ALL PRODUCTS: $prev_snapshot_daily <-> $snapshot_output_file");
		$ret = execute_command("compare_statistics_snapshots.pl -d $CONFIG_FILE $ALL_PRODUCTS_DIR $prev_snapshot_daily $snapshot_output_file $BUGS_WHICH_CANNOT_BE_VERIFIED_FILE_NAME");
		if ($ret =~ /ERROR/) {
			# TODO - if the file does not contain the header line then wget failed and file should be removed... shoud be..?
			$errors .= $ret . "\n";
		}
		
		# previous weekly snapshot (last Monday)
		($year, $month, $day, $hour, $minute) = ($s =~ /(\d+)-(\d+)-(\d+)_(\d+)-(\d+)\.csv/);
		$dow = strftime "%u", (0, $minute, $hour, $day, $month-1, $year-1900);
		if ($dow == 1) {
			# compare new snapshot with previous one for ALL PRODUCTS - prepare data for printing them on the web page (weekly view)
			info("weekly view: ALL PRODUCTS: $prev_snapshot_weekly <-> $snapshot_output_file");
			$ret = execute_command("compare_statistics_snapshots.pl -w $CONFIG_FILE $ALL_PRODUCTS_DIR $prev_snapshot_weekly $snapshot_output_file $BUGS_WHICH_CANNOT_BE_VERIFIED_FILE_NAME");
			if ($ret =~ /ERROR/) {
				$errors .= $ret . "\n";
			}
			
			$prev_snapshot_weekly = $s;
		}
	}
}
closedir S_DIR;

# remove last snapshot file and run 'fetch_statistics_from_bugzilla.pl' to create variables file, etc.
execute_command("rm $snapshot_all_output_raw_dir/$snapshot_output_file");
execute_command("fetch_statistics_from_bugzilla.pl $CONFIG_FILE");

execute_command("rm -rf $TMP_DIR");
info("END");

close LOG;

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
	return @_[2];
}

sub read_products_list {
	%PRODUCTS = ();
	open(FILE1, "<", $PRODUCTS_CONFIG_FILE);
	while ( chomp($a = <FILE1>) ) {
		if ( $a =~ /\#/ || $a eq "" ) {
			next;
		}
		($product, $params) = split(";", $a);
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

sub rand_str
{
	my $length_of_randomstring=shift; # the length of the random string to generate

	my @chars=('a'..'z','A'..'Z','0'..'9','_');
	my $random_string;
	foreach (1..$length_of_randomstring) 
	{
		# rand @chars will generate a random 
		# number between 0 and scalar @chars
		$random_string.=$chars[rand @chars];
	}
	return $random_string;
}

# ==================================================


