# Access control module
# Copyright (C) 2008, LinuxRulz
# 
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
# 
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# 
# You should have received a copy of the GNU General Public License along
# with this program; if not, write to the Free Software Foundation, Inc.,
# 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.


package cbp::modules::AccessControl;

use strict;
use warnings;


use cbp::logging;
use cbp::dblayer;


# User plugin info
our $pluginInfo = {
	name 			=> "Access Control Plugin",
	check 			=> \&check,
	init		 	=> \&init,
};


# Module configuration
my %config;


# Create a child specific context
sub init {
	my $server = shift;
	my $inifile = $server->{'inifile'};

	# Defaults
	$config{'enable'} = 0;

	# Parse in config
	if (defined($inifile->{'accesscontrol'})) {
		foreach my $key (keys %{$inifile->{'accesscontrol'}}) {
			$config{$key} = $inifile->{'accesscontrol'}->{$key};
		}
	}

	# Check if enabled
	if ($config{'enable'} =~ /^\s*(y|yes|1|on)\s*$/i) {
		$server->log(LOG_NOTICE,"  => AccessControl: enabled");
		$config{'enable'} = 1;
	}
}


# Destroy
sub finish {
}



# Check the request
sub check {
	my ($server,$request) = @_;
	

	# If we not enabled, don't do anything
	return undef if (!$config{'enable'});
	$server->log(LOG_DEBUG,"enabled");

	# We only valid in the RCPT state
	return undef if (!defined($request->{'protocol_state'}) || $request->{'protocol_state'} ne "RCPT");
	$server->log(LOG_DEBUG,"protocol_state");


	use Data::Dumper;
	$server->log(LOG_DEBUG,Dumper($request));

	# Our verdict and data
	my ($verdict,$verdict_data);

	# Loop with priorities, high to low
	foreach my $priority (sort {$b <=> $a} keys %{$request->{'_policy'}}) {
		
		foreach my $policyID (@{$request->{'_policy'}->{$priority}}) {
			$server->log(LOG_DEBUG, "Priority: '$priority', Policy: '$policyID'\n");

			my $sth = DBSelect("
				SELECT
					Verdict, Data
				FROM
					access_control
				WHERE
					PolicyID = ".DBQuote($policyID)."
					AND Disabled = 0
			");
			if (!$sth) {
				$server->log(LOG_ERR,"Database query failed: ".cbp::dblayer::Error());
				return undef;
			}
			my $row = $sth->fetchrow_hashref();
			DBFreeRes($sth);
			# If no result, next
			next if (!$row);

			# Setup result
			$verdict = $row->{'Verdict'};
			$verdict_data = $row->{'Data'};

			$server->log(LOG_DEBUG, "Verdict: '".$row->{'Verdict'}."', Data: '".$row->{'Data'}."'\n");
		}

		# Last if we found something
		last if ($verdict);
	}

	return ($verdict,$verdict_data);
}



1;
# vim: ts=4