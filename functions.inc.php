<?php
	// Copyright 2016 Marcel Haupt
	// http://marcel-haupt.eu/
	//
	// Licensed under the Apache License, Version 2.0 (the "License");
	// you may not use this file except in compliance with the License.
	// You may obtain a copy of the License at
	//
	// http ://www.apache.org/licenses/LICENSE-2.0
	//
	// Unless required by applicable law or agreed to in writing, software
	// distributed under the License is distributed on an "AS IS" BASIS,
	// WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
	// See the License for the specific language governing permissions and
	// limitations under the License.
	//
	// Github Project: https://github.com/cbacon93/DCSServerStats

	
class SimStats {
	protected $mysqli;
	
	public function SimStats(mysqli $mysqli) {
		$this->mysqli = $mysqli;
	}
	
	public function echoSiteContent() 
	{
		if (isset($_GET['pid'])) 
		{
			$this->echoPilotStatistic($_GET['pid']);
		}
		else if (isset($_GET['flights'])) 
		{
			echo "<h2>Flights</h2><br><br>";
			$this->echoFlightsTable();
		} 
		else if (isset($_GET['aircrafts'])) 
		{
			echo "<h2>Aircrafts</h2><br><br>";
			$this->echoAircraftsTable();
		} 
		else
		{
			echo "<h2>Pilots</h2><br><br>";
			$this->echoPilotsTable();
		}
	}
		
	public static function timeToString($time) {
		$flight_hours = floor($time / 60 / 60);
		$flight_mins = floor($time / 60) - $flight_hours * 60;
		$flight_secs = $time  - $flight_mins * 60 - $flight_hours * 3600;
		
		if ($flight_mins < 10)
			$flight_mins = '0' . $flight_mins;
		if ($flight_secs < 10)
			$flight_secs = '0' . $flight_secs;
			
		return "$flight_hours:$flight_mins:$flight_secs";
	}
	
	
	function echoUpdateInfo() {
		$result = $this->mysqli->query("SELECT * FROM bms_parser_log ORDER BY id DESC LIMIT 1");
		if ($row = $result->fetch_object()) {
		
			echo "Last update at " . date('G:i', $row->time) . " processed " . $row->events . " events in " . $row->durationms .  " ms";
		}
	}
	
	
	
	public function getPilotsTable() {
		$pilots = array();
		
		$result = $this->mysqli->query("SELECT * FROM bms_pilots WHERE name<>'AI' AND disp_name<>'AI' ORDER BY flighttime DESC");
		while($row = $result->fetch_object()) {
			$pilots[] = $row;
		}
		
		return $pilots;
	}
	
	
	
	public function getPilotsFlightsTable($pilotid = -1) {
		$flights = array();
		
		if ($pilotid > 0) {
			$prep = $this->mysqli->prepare("SELECT 0, '', aircrafts.name, flights.id, flights.takeofftime, flights.landingtime, flights.recordtime FROM bms_flights AS flights, bms_aircrafts AS aircrafts WHERE flights.pilotid=? AND aircrafts.id=flights.aircraftid ORDER BY flights.takeofftime DESC LIMIT 10");
			$prep->bind_param('i', $pilotid);
		} else {
			$prep = $this->mysqli->prepare("SELECT pilots.id AS pid, pilots.disp_name AS pname, aircrafts.name, flights.id, flights.takeofftime, flights.landingtime, flights.recordtime FROM bms_flights AS flights, bms_aircrafts AS aircrafts, bms_pilots AS pilots WHERE pilots.id=flights.pilotid AND aircrafts.id=flights.aircraftid AND pilots.name<>'AI' AND pilots.disp_name<>'AI' ORDER BY flights.takeofftime DESC LIMIT 30");
		}
		$prep->execute();
		
		$row = new stdClass();
		$prep->bind_result($row_pilotid, $row_pilotname, $row_acname, $row_id, $row_takeofftime, $row_landingtime, $row_recordtime);
		
		while($prep->fetch()) {
			$flight = new stdClass();
			$flight->pilotid = $row_pilotid;
			$flight->pilotname = $row_pilotname;
			$flight->acname = $row_acname;
			$flight->id = $row_id;
			$flight->takeofftime = $row_takeofftime;
			$flight->landingtime = $row_landingtime;
			$flight->duration = $flight->landingtime - $flight->takeofftime;
			$flight->recordtime = $row_recordtime;
			$flights[] = $flight;
		}
		$prep->close();
		
		return $flights;
	}
	
	
	public function getPilotsAircraftTable($pilotid = -1) {
		$aircrafts = array();
		
		if ($pilotid > 0) {
			$prep = $this->mysqli->prepare("SELECT pilot_aircrafts.flights, aircrafts.name, pilot_aircrafts.flighttime FROM bms_pilot_aircrafts AS pilot_aircrafts, bms_aircrafts AS aircrafts, bms_pilots AS pilots WHERE pilot_aircrafts.pilotid=? AND pilots.id = pilot_aircrafts.pilotid AND pilot_aircrafts.aircraftid=aircrafts.id ORDER BY pilot_aircrafts.flighttime DESC");
			$prep->bind_param('i', $pilotid);
		} else {
			$prep = $this->mysqli->prepare("SELECT aircrafts.flights, aircrafts.name, aircrafts.flighttime FROM bms_aircrafts AS aircrafts ORDER BY aircrafts.flighttime DESC");
		}
		$prep->execute();
		
		$row = new stdClass();
		$prep->bind_result($row_flights, $row_acname, $row_time);
		
		while($prep->fetch()) {
			$aircraft = new stdClass();
			$aircraft->flights = $row_flights;
			$aircraft->acname = $row_acname;
			$aircraft->time = $row_time;
			
			$aircrafts[] = $aircraft;
		}
		$prep->close();
		
		return $aircrafts;
	}
	
	
	public function quicksort($seq, $key) {
	    if(!count($seq)) return $seq;
		$pivot= $seq[0];
	    $low = array();
	    $high = array();
	    $length = count($seq);
	    for($i=1; $i < $length; $i++) {
	        if($seq[$i]->$key <= $pivot->$key) {
	            $low [] = $seq[$i];
	        } else {
	            $high[] = $seq[$i];
	        }
	    }
		return array_merge($this->quicksort($low, $key), array($pivot), $this->quicksort($high, $key));
	}
		
		
		
	public function echoPilotsTable() {
		echo "<table class='table_stats'>";
		echo "<tr class='table_header'><th>Pilot</th><th>Flights</th><th>Flight time</th><th>last active</th></tr>";
		
		$pilots = $this->getPilotsTable();
		
		foreach($pilots as $aid=>$pilot) {
						
			echo "<tr onclick=\"window.document.location='?pid=" . $pilot->id . "'\" class='table_row_" . $aid%2 . "'><td>" . $pilot->disp_name . "</td><td>" . $pilot->flights . "</td><td>" . $this->timeToString($pilot->flighttime) . "</td><td>" . date('d.m.Y', $pilot->lastactive) . "</td></tr>";
			
			
		}
		
		if (sizeof($pilots) == 0) {
			echo "<tr><td style='text-align: center' colspan='8'>No Pilots listed</td></tr>";
		}
		
		echo "</table>";
	}
	
	
	public function getPilotsStatistic($pilotid) {
		//get pilot information
		$prep = $this->mysqli->prepare("SELECT id, name, disp_name, flighttime, flights, lastactive FROM bms_pilots WHERE id=?");
		$prep->bind_param('i', $pilotid);
		$prep->execute();
		
		$row = new stdClass();
		$prep->bind_result($row->id, $row->name, $row->disp_name, $row->flighttime, $row->flights, $row->lastactive);
		if ($prep->fetch()) {
			$prep->close();
			return $row;
		}
		$prep->close();
		return false;
	}
	
	
	
	public function echoPilotsFlightsTable($pilotid) {	
		$flights = $this->getPilotsFlightsTable($pilotid);
		
		echo "<table class='table_stats'>";
		echo "<tr class='table_header'><th>Aircraft</th><th>Takeoff</th><th>Landing</th><th>Duration</th></tr>";
		
		foreach($flights as $aid=>$flight) {
			echo "<tr class='table_row_" . $aid%2 . "'><td>" . $flight->acname . "</td><td>" . date('d.m.Y - H:i', $flight->takeofftime) . "</td><td>" . date('d.m.Y - H:i', $flight->landingtime) . "</td><td>" . $this->timeToString($flight->duration) . "</td></tr>";
		}
		
		if (sizeof($flights) == 0) {
			echo "<tr><td style='text-align: center' colspan='6'>No Flights listed</td></tr>";
		}
		
		echo "</table><br><br>";
	}
	
	
	public function echoFlightsTable() {	
		$flights = $this->getPilotsFlightsTable();
		
		echo "<table class='table_stats'>";
		echo "<tr class='table_header'><th>Record date</th><th>Pilot</th><th>Aircraft</th><th>Takeoff</th><th>Landing</th><th>Duration</th></tr>";
		
		foreach($flights as $aid=>$flight) {
			echo "<tr class='table_row_" . $aid%2 . "'><td>" . date('d.m.Y', $flight->recordtime) . "</td><td>" . $flight->pilotname . "</td><td>" . $flight->acname . "</td><td>" . date('d.m.Y - H:i', $flight->takeofftime) . "</td><td>" . date('d.m.Y - H:i', $flight->landingtime) . "</td><td>" . $this->timeToString($flight->duration) . "</td></tr>";
		}
		
		if (sizeof($flights) == 0) {
			echo "<tr><td style='text-align: center' colspan='7'>No Flights listed</td></tr>";
		}
		
		echo "</table><br><br>";
	}
	
	
	public function echoPilotsAircraftsTable($pilotid) {
		$flights = $this->getPilotsAircraftTable($pilotid);
		
		echo "<table class='table_stats'>";
		echo "<tr class='table_header'><th>Aircraft</th><th>Flights</th><th>Flight time</th></tr>";
		
		foreach($flights as $aid=>$flight) {
			echo "<tr class='table_row_" . $aid%2 . "'><td>" . $flight->acname . "</td><td>" . $flight->flights . "</td><td>" . $this->timeToString($flight->time) . "</td></tr>";
		}
		
		if (sizeof($flights) == 0) {
			echo "<tr><td style='text-align: center' colspan='6'>No Aircrafts listed</td></tr>";
		}
		
		
		echo "</table><br><br>";
	}
	
	
	public function echoAircraftsTable() {
		$flights = $this->getPilotsAircraftTable();
		
		echo "<table class='table_stats'>";
		echo "<tr class='table_header'><th>Aircraft</th><th>Flights</th><th>Flight time</th></tr>";
		
		foreach($flights as $aid=>$flight) {
			echo "<tr class='table_row_" . $aid%2 . "'><td>" . $flight->acname . "</td><td>" . $flight->flights . "</td><td>" . $this->timeToString($flight->time) . "</td></tr>";
		}
		
		if (sizeof($flights) == 0) {
			echo "<tr><td style='text-align: center' colspan='6'>No Aircrafts listed</td></tr>";
		}
		
		echo "</table><br><br>";
	}
	
	
	
	public function echoPilotStatistic($pilotid) {
		
		if ($pilot = $this->getPilotsStatistic($pilotid)) {
			
			$pilotid = $pilot->id;
					
			echo "<h2>Pilot " . $pilot->disp_name . "</h2><br><br>";
			echo "<table class='table_stats'><tr class='table_row_0'><td>Total Flight Time: </td><td>" . $this->timeToString($pilot->flighttime) . "</td></tr>";
			echo "<tr class='table_row_1'><td>Flights: </td><td>" . $pilot->flights . "</td></tr>";
			echo "<tr class='table_row_0'><td>Last Activity: </td><td>" . date('d.m.Y', $pilot->lastactive) . "</td></tr>";
			echo "</table><br><br>";
			
			
			echo "<b>Last Flights:</b>";
			$this->echoPilotsFlightsTable($pilotid);
			
			echo "<b>Flown Airplanes</b>";
			$this->echoPilotsAircraftsTable($pilotid);
					
		} else {
			echo "Pilot not found!";
		}
	}
} 	
?>