#!/usr/bin/perl

#===================================================================================
# BAM Manager (Bugzilla Automated Metrics Manager): libcontentaction.pl
#
# Copyright 2011, Comarch SA
# Maintainers: 	Krystian Jedrzejowski <krystian.jedrzejowski@comarch.com>,
# 				Kamil Marek <kamil.marek@comarch.com>
# Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php
#
# Date: Thu Jul 13 11:56:00 EET 2011
#===================================================================================

use POSIX qw(strftime);
use Cwd 'abs_path';

my $LOG_FILE = abs_path();
$LOG_FILE =~ s/lib//g;
$LOG_FILE = "$LOG_FILE/log/syslog";
die "Log file does not exists: $LOG_FILE - exit." unless (-e $LOG_FILE);

open(LOG, ">>", $LOG_FILE);
if ($ARGV[0] eq "--start") {
	if ($#ARGV != 2) {
		print "METHOD: --start ERROR: too few or too many argument(s)\n$help";
		exit -1;
		info("METHOD: --start ERROR: too few or too many argument(s)");
	}
	else {
		$USER=$ARGV[1];
		$ACTION=$ARGV[2];
		info("=================================START=========================================");
		info("User: $USER. Action: $ACTION");
	}
	break;
}

elsif ($ARGV[0] eq "--end") {
	if ($#ARGV != 2) {
		print "METHOD: --end ERROR: too few or too many argument(s)\n$help";
		exit -1;
		info("METHOD: --end ERROR: too few or too many argument(s)");
	}
	else {
		$USER=$ARGV[1];
		$ACTION=$ARGV[2];
		info("User: $USER. Action: $ACTION");
		info("=================================END=========================================");
	}
	break;
}

elsif ($ARGV[0] eq "--save") {
	info("METHOD: --save");
	
	if ($#ARGV != 2) {
		print "METHOD: --save ERROR: too few or too many argument(s)\n$help";
		exit -1;
		info("METHOD: --save ERROR: too few or too many argument(s)");
	}
	else {
		$FILE_NAME=$ARGV[1];
		$FILE_CONTENT=$ARGV[2];
		info("saving $FILE_CONTENT to $FILE_NAME");
		$FILE_CONTENT=~ s/__semicolon__/;/g;
		$FILE_CONTENT=~ s/__space__/ /g;
		$FILE_CONTENT=~ s/__hash__/#/g;
		$FILE_CONTENT=~ s/__colon__/&/g;

		open(OUTFILE, '>>', "$FILE_NAME");
		print OUTFILE "$FILE_CONTENT\n";
		close OUTFILE;
	}
	break;
}
	
elsif ($ARGV[0] eq "--clear") {
	info("METHOD: --clear");
	if ($#ARGV != 1) {
	print "METHOD: --clear ERROR: too few or too many argument(s)\n$help";
	exit -1;
	}
	
	else {
		$FILE_NAME=$ARGV[1];
		execute_command("echo -n > $FILE_NAME");
	}
	break;
}
	
elsif ($ARGV[0] eq "--refresh") {
	info("METHOD: --refresh");
	if ($#ARGV != 1) {
	print "METHOD: --refresh ERROR: too few or too many argument(s)\n$help";
	exit -1;
	}
	
	else {
		$CONFIG_FILE=$ARGV[1];
		$STATISTICS = read_config_entry("STATISTICS");
		$COMMON_PARAMS_FILE = read_config_entry("COMMON_PARAMS_FILE");
		$STATISTICS_BASE_PATH = read_config_entry("STATISTICS_BASE_PATH");
		$STATISTICS_BASE_PATH = trim($STATISTICS_BASE_PATH);
		$DATA_PATH = "$STATISTICS_BASE_PATH/$STATISTICS";
		info("DATA_PATH:$DATA_PATH");
		chomp($date = strftime "%Y-%m-%d", localtime);
		info("date:$date");
		# Remove present-day raw_data files for selected statistics
		execute_command("rm $DATA_PATH/*/raw_data/\"$date\"_*");
	}
	break;
}

elsif ($ARGV[0] eq "--refresh-all") {
	info("METHOD: --refresh-all");
	if ($#ARGV == 3) {
		$COMMON_PARAMS_FILE=$ARGV[1];
		$FETCH_STATISTICS_USER=$ARGV[2];
		$FETCH_STATISTICS_FILE=$ARGV[3];
		$STATISTICS_BASE_PATH = read_config_entry("STATISTICS_BASE_PATH");
		$STATISTICS_BASE_PATH = trim($STATISTICS_BASE_PATH);
		chomp($date = strftime "%Y-%m-%d", localtime);
		$DATA_PATH="$STATISTICS_BASE_PATH/*/*/raw_data/\"$date\"_*";
		# Remove present-day raw_data files for ALL statistics
		$ret = execute_command("rm $DATA_PATH");
		if ($ret ne "") {
			info("ERROR: $ret");
		}
		else {
			info("Present-day data successfully removed.");
		}
		# Fetch data for ALL statistics
		$response = execute_command("su - $FETCH_STATISTICS_USER -c '$FETCH_STATISTICS_FILE $CONFIG_FILE'");
		if ($response ne "") {
			info("ERROR: $response");
		}
		else {
			info("Statistics have been successfully updated.");
		}
		return $response;
	}
	else {
		print "METHOD: --refresh-all ERROR: too few or too many argument(s)\n$help";
		exit -1;
	}
	break;
}

elsif ($ARGV[0] eq "--fetch") {
	info("METHOD: --fetch");
	if ($#ARGV != 3) {
		print "--fetch ERROR: too few or too many argument(s)\n$help";
		exit -1;
	}
	
	else {
		$FETCH_STATISTICS_USER=$ARGV[1];
		info("FETCH_STATISTICS_USER: $ARGV[1]");
		$FETCH_STATISTICS_FROM_BUGZILLA_FILE=$ARGV[2];
		info("FETCH_STATISTICS_FROM_BUGZILLA_FILE: $ARGV[2]");
		$STATISTICS_CONFIG_FILE=$ARGV[3];
		info("STATISTICS_CONFIG_FILE: $ARGV[3]");
		# Fetch data as BAM user
		$ret = execute_command("su - $FETCH_STATISTICS_USER -c '$FETCH_STATISTICS_FROM_BUGZILLA_FILE $STATISTICS_CONFIG_FILE'");
		if ($ret ne "") {
			info("ERROR: BAM could not fetch data: $ret");
			exit -1;
		}
		return $ret;
	}
	break;
}

elsif ($ARGV[0] eq "--subset") {
	info("METHOD: --subset");
	if ($#ARGV != 4) {
		print "METHOD: --subset ERROR: too few or too many argument(s)\n$help";
		exit -1;
	}
	
	else {
		$FETCH_PATH=$ARGV[1];
		info("FETCH_PATH: $ARGV[1]");
		$FILE_PATH=$ARGV[2];
		info("FILE_PATH: $ARGV[2]");
		$DATE=$ARGV[3];
		info("DATE: $ARGV[3]");
		$FETCH_STATISTICS_USER=$ARGV[4];
		info("FETCH_STATISTICS_USER: $ARGV[4]");
		$ret = execute_command("su - $FETCH_STATISTICS_USER -c '$FETCH_PATH $FILE_PATH $DATE'");
		
		if ($ret ne "") {
			info("ERROR: BAM could not fetch data: $ret");
			exit -1;
		}
		return $ret;
	}
	break;
}

elsif ($ARGV[0] eq "--move") {
	info("METHOD: --move");
	if ($#ARGV != 2) {
	print "METHOD: --move ERROR: too few or too many argument(s)\n$help";
	exit -1;
	}
	
	else {
		$TEMP_FILE_PATH=$ARGV[1];
		$FILE_PATH=$ARGV[2];
		if (-e trim(execute_command("dirname $FILE_PATH"))) {
			if (! -e $FILE_PATH) {
				execute_command("touch $FILE_PATH");
				execute_command("cat $TEMP_FILE_PATH > $FILE_PATH");
				execute_command("rm $TEMP_FILE_PATH");
			}
			else {
				execute_command("echo -n > $FILE_PATH");
				execute_command("cat $TEMP_FILE_PATH > $FILE_PATH");
				execute_command("rm $TEMP_FILE_PATH");
			}
		}
		else {
			info ("DIR_PATH: $DIR_PATH does not exist.");
			execute_command("mkdir `dirname $FILE_PATH`");
			execute_command("touch $FILE_PATH");
			execute_command("cat $TEMP_FILE_PATH > $FILE_PATH");
			execute_command("rm $TEMP_FILE_PATH");
		}
	}
	break;
}

elsif ($ARGV[0] eq "--remove") {
	info("METHOD: --remove");
	if ($#ARGV != 1) {
		print "METHOD: --remove ERROR: too few or too many argument(s)\n$help";
		exit -1;
	}
	else {
		$CONFIG_FILE=$ARGV[1];
		info("CONFIG_FILE: $ARGV[1]");
		$STATISTICS = read_config_entry("STATISTICS");
		$COMMON_PARAMS_FILE = read_config_entry("COMMON_PARAMS_FILE");
		$STATISTICS_BASE_PATH = read_config_entry("STATISTICS_BASE_PATH");
		$STATISTICS_BASE_PATH = trim($STATISTICS_BASE_PATH);
		$DATA_PATH = "$STATISTICS_BASE_PATH/$STATISTICS";
		$DIR_CONFIG_FILE=trim(execute_command("dirname $CONFIG_FILE"));
		info("DATA_PATH:$DATA_PATH");
		execute_command("rm -r $DATA_PATH/");
		execute_command("rm $CONFIG_FILE");
		$COUNT_FILES=execute_command("ls $DIR_CONFIG_FILE | wc -l");
		if ($COUNT_FILES == 0) {
			execute_command("rmdir $DIR_CONFIG_FILE/");
		}
		return $date;
	}
	break;
}

elsif ($ARGV[0] eq "--rename") {
	info("METHOD: --rename");
	if ($#ARGV != 2) {
		print "METHOD: --rename ERROR: too few or too many argument(s)\n$help";
		exit -1;
	}
	
	else {
		$CONFIG_FILE=$ARGV[1];
		$CURRENT_NAME=$ARGV[2];
		$STATISTICS = read_config_entry("STATISTICS");
		$COMMON_PARAMS_FILE = read_config_entry("COMMON_PARAMS_FILE");
		$STATISTICS_BASE_PATH = read_config_entry("STATISTICS_BASE_PATH");
		$STATISTICS_BASE_PATH = trim($STATISTICS_BASE_PATH);
		$DATA_PATH = "$STATISTICS_BASE_PATH/$STATISTICS";
		$CURRENT_DATA_PATH = "$STATISTICS_BASE_PATH/$CURRENT_NAME";
		if ($DATA_PATH ne $CURRENT_DATA_PATH){
			info("DATA_PATH:$DATA_PATH");
			execute_command("mv $DATA_PATH $CURRENT_DATA_PATH");
			return $date;
		}
		break;
	}
	break;
}

elsif ($ARGV[0] eq "--remove-file") {
	info("METHOD: --remove-file");
	if ($#ARGV != 1) {
		print "METHOD: --remove ERROR: too few or too many argument(s)\n$help";
		exit -1;
	}
	
	else {
		$FILE=$ARGV[1];
		execute_command("rm $FILE");
	}
}
	
elsif ($ARGV[0] eq "--get-users") {
	info("--get-users");
	if ($#ARGV != 0) {
		print "--get-users ERROR: too few or too many argument(s)\n$help";
		exit -1;
	}
	
	else {
		execute_command("cp -r users /tmp/bammanager_tmp");
		execute_command("chown -R www-data /tmp/bammanager_tmp");
	}
	break;
}
else { 
	print "ERROR: $ARGV[0] is wrong control argument";
	exit -1;
}

# ======================

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

# trim function to remove whitespace from the start and end of the string
sub trim {
	my $string = shift;
	$string =~ s/^\s+//;
	$string =~ s/\s+$//;
	return $string;
}

# ===========DEBUG FUNCTIONS==========
sub execute_command {
    my $command = $_[0];
    info("COMMAND: $command");
    my $ret = `$command 2>&1`;
    info("RESPONSE: $ret");
    return $ret;
}

sub info {
    if ($_[0] ne "") {
        chomp($time = `date +%Y-%m-%d-%H-%M-%S`);
        print LOG "$time: @_\n";
    }
}