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
// Github Project: https://github.com/cbacon93/BMSStats


class XML_Import {
	private $flight_list = array();
	private $bms_parser_log;
	
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
	
	
	public function printDebug() {
		echo "Following Flights have been found: <br>";
		echo "<table class='table_stats'>";
		echo "<tr class='table_header'><th>Record date</th><th>Pilot</th><th>Aircraft</th><th>Takeoff</th><th>Landing</th><th>Duration</th></tr>";
		
		foreach($this->flight_list as $flight) {
			echo "<tr><td>" . date('d.m.Y', $flight->recordtime) . "</td><td>" . $flight->pilot . "</td><td>" . $flight->aircraft . "</td><td>" . date('d.m.Y - H:i', $flight->takeofftime) . "</td><td>" . date('d.m.Y - H:i', $flight->landingtime) . "</td><td>" . $this->timeToString($flight->landingtime - $flight->takeofftime) . "</td></tr>";
		}
		
		echo "</table><br><br>";
	} 
	
	
	public function writeToDatabase($mysqli) {
		foreach($this->flight_list as $flight) {
			if ($this->checkFlight($mysqli, $flight)) {
				echo "flight added<br>";
				
				
				$duration = $flight->landingtime - $flight->takeofftime;
				$pilotid = $this->getPilotId($mysqli, $flight->pilot);
				$aircraftid = $this->getAircraftId($mysqli, $flight->aircraft);
				$pilotaircraftid = $this->getPilotAircraftId($mysqli, $pilotid, $aircraftid);
				
				//insert data
				
				$prep = $mysqli->prepare("INSERT INTO bms_flights SET recordtime=?, pilotid=?, aircraftid=?, takeofftime=?, landingtime=?");
				$prep->bind_param('iiiii', $flight->recordtime, $pilotid, $aircraftid, $flight->takeofftime, $flight->landingtime);
				$prep->execute();
				$prep->close();
				
				$prep = $mysqli->prepare("UPDATE bms_pilots SET flights=flights+1, flighttime=flighttime+?, lastactive=? WHERE id=?");
				$prep->bind_param('iii', $duration, $flight->recordtime, $pilotid);
				$prep->execute();
				$prep->close();
				
				$prep = $mysqli->prepare("UPDATE bms_aircrafts SET flights=flights+1, flighttime=flighttime+? WHERE id=?");
				$prep->bind_param('ii', $duration, $aircraftid);
				$prep->execute();
				$prep->close();
				
				$prep = $mysqli->prepare("UPDATE bms_pilot_aircrafts SET flights=flights+1, flighttime=flighttime+? WHERE id=?");
				$prep->bind_param('ii', $duration, $pilotaircraftid);
				$prep->execute();
				$prep->close();
				
			} else {
				echo "duplicate flight not added<br>";
			}
		}
		
		
		//end parsing
		$this->bms_parser_log->endtimems = microtime(true) * 1000;
		$this->bms_parser_log->durationms = round($this->bms_parser_log->endtimems - $this->bms_parser_log->starttimems);
		
		//write log entry
		$query = "INSERT INTO bms_parser_log SET time='" . $this->bms_parser_log->time . "', durationms='" . $this->bms_parser_log->durationms . "', events='" . $this->bms_parser_log->events . "'";
		$mysqli->query($query);
	}
	
	private function getPilotId($mysqli, $pilot) {
		$prep = $mysqli->prepare("SELECT id FROM bms_pilots WHERE name=? LIMIT 1");
		$prep->bind_param('s', $pilot);
		$prep->execute();
		$prep->store_result();
		
		//create new pilot
		if ($prep->num_rows == 0) {
			$prep->close();
			
			$prep = $mysqli->prepare("INSERT INTO bms_pilots SET name=?, disp_name=name");
			$prep->bind_param('s', $pilot);
			$prep->execute();
			$prep->close();
			
			return $mysqli->insert_id;
		}
		
		//else, get pilot id
		$id = 0;
		$prep->bind_result($id);
		$prep->fetch();
		$prep->close();
		return $id;
	}
	
	
	private function getAircraftId($mysqli, $aircraft) {
		$prep = $mysqli->prepare("SELECT id FROM bms_aircrafts WHERE name=? LIMIT 1");
		$prep->bind_param('s', $aircraft);
		$prep->execute();
		$prep->store_result();
		
		//create new pilot
		if ($prep->num_rows == 0) {
			$prep->close();
			
			$prep = $mysqli->prepare("INSERT INTO bms_aircrafts SET name=?");
			$prep->bind_param('s', $aircraft);
			$prep->execute();
			$prep->close();
			
			return $mysqli->insert_id;
		}
		
		//else, get pilot id
		$id = 0;
		$prep->bind_result($id);
		$prep->fetch();
		$prep->close();
		return $id;
	}
	
	private function getPilotAircraftId($mysqli, $pilotid, $aircraftid) {
		$prep = $mysqli->prepare("SELECT id FROM bms_pilot_aircrafts WHERE pilotid=? AND aircraftid=? LIMIT 1");
		$prep->bind_param('ii', $pilotid, $aircraftid);
		$prep->execute();
		$prep->store_result();
		
		//create new pilot
		if ($prep->num_rows == 0) {
			$prep->close();
			
			$prep = $mysqli->prepare("INSERT INTO bms_pilot_aircrafts SET pilotid=?, aircraftid=?");
			$prep->bind_param('ii', $pilotid, $aircraftid);
			$prep->execute();
			$prep->close();
			
			return $mysqli->insert_id;
		}
		
		//else, get pilot id
		$id = 0;
		$prep->bind_result($id);
		$prep->fetch();
		$prep->close();
		return $id;
	}
	
	
	private function putFlight($_recordtime, $_takeofftime, $_landingtime, $_pilot, $_aircraft) {
		$event = new stdClass();
		$event->recordtime = $_recordtime;
		$event->takeofftime = $_takeofftime;
		$event->landingtime = $_landingtime;
		$event->pilot = $_pilot;
		$event->aircraft = $_aircraft;
		
		$this->flight_list[] = $event;
	}
	
	//prevent double flight insertion
	private function checkFlight($mysqli, $flight) {
		
		//check obvious parameters
		$duration = $flight->landingtime - $flight->takeofftime;
		if ($duration < 0 || $duration > 3600 * 12) {
			return false;
		}
		
		
		$prep = $mysqli->prepare("SELECT flights.id FROM bms_flights AS flights, bms_pilots AS pilots, bms_aircrafts AS aircrafts WHERE flights.pilotid=pilots.id AND flights.aircraftid=aircrafts.id AND pilots.name=? AND aircrafts.name=? AND (flights.takeofftime<=? AND flights.landingtime>=? OR flights.takeofftime<=? AND flights.landingtime>=?) AND (ABS(flights.recordtime-?) < 3600)");
		$prep->bind_param('ssiiiii', $flight->pilot, $flight->aircraft, $flight->takeofftime, $flight->takeofftime, $flight->landingtime, $flight->landingtime, $flight->recordtime);
		
		$prep->execute();
		$prep->store_result();
		
		if ($prep->num_rows == 0) {
			$prep->close();
			return true;
		}
		
		$prep->close();
		return false;
	}

	function XML_Import($filename) {
		
		//start logging
		$this->bms_parser_log = new stdClass();
		$this->bms_parser_log->time = time();
		$this->bms_parser_log->starttimems = microtime(true) * 1000;
		$this->bms_parser_log->events = 0;
				

		$xml = simplexml_load_file($filename);
		$recordsource = $xml->FlightRecording->Source;
		$missionstarttime = $this->getUnixTimeFromTimestamp($xml->Mission->MissionTime);
		$recordtime = $this->getUnixTimeFromTimestamp($xml->FlightRecording->RecordingTime);
		$missiontime = 0;

		$takeoff_event = array();
		
		//loop through events
		foreach($xml->Events->Event as $event) {
			//get time
			$missiontime = $event->Time;
			$id = intval($event->PrimaryObject['ID']);
			$type = $this->getUnitType($event->PrimaryObject->Type);
			$this->bms_parser_log->events++;
			
			//TAKEOFF
			if ($event->Action == "HasTakeOff" && $type == "AIRPLANE") {
				if ($this->filterAiName($event->PrimaryObject->Pilot) != "AI") {
					$takeoff_event[$id] = $missiontime;	
				}
			}
			
			
			//landing
			if (array_key_exists($id, $takeoff_event) && 
				($event->Action == "HasLanded" || $event->Action == "HasBeenDestroyed") && 
				$type == "AIRPLANE") 
			{
				$this->putFlight($recordtime, $missionstarttime+$takeoff_event[$id], $missionstarttime+$missiontime, strval($event->PrimaryObject->Pilot), $this->filterAircraftNames($event->PrimaryObject->Name));
				
				unset($takeoff_event[$id]);
			}
		}
	}
	
	//functions
	
	private function getUnixTimeFromTimestamp($timestamp) 
	{
		$split = explode("T", $timestamp);
		$datestr = $split[0];
		$timestr = substr($split[1], 0, strlen($split[1])-1);
		
		//remove milliseconds
		if ($pos = strpos($timestr, ".")) {
			$timestr = substr($timestr, 0, $pos);
		}
		
		$datetime = DateTime::createFromFormat("Y-m-d H:i:s", $datestr . " " . $timestr, new DateTimeZone("UTC"));
		return $datetime->getTimestamp();
	}
	
	private function getUnitType($type) {
		$rtype = "";
		switch($type) {
			case "Helicopter":
				$rtype = "HELICOPTER";
				break;
			case "Aircraft":
				$rtype = "AIRPLANE";
				break;
			case "Tank":
			case "Ground":
			case "Vehicle":
				$rtype = "GROUND";
				break;
			case "Shell":
				$rtype = "SHELL";
				break;
			case "Missile":
				$rtype = "MISSILE";
				break;
			case "Rocket":
				$rtype = "ROCKET";
				break;
			case "Bomb":
				$rtype = "BOMB";
				break;
		}
		return $rtype;
	}
	
	
	function filterAircraftNames($name) {
		$name = str_replace("Mirage 2000", "M-2000", $name);
		
		//general
		/*if ($pos = strpos($name, " ")) {
			$name = substr($name, 0, $pos);
		}*/
		
		return $name;
	}
	
	
	function filterAiName($name) {
		if (strlen($name) <= 0) return "AI";
		
		//turn AI to AI
		switch($name) {
			case("AWACS - Magic"):
				$name = "AI";
		}
		
		//generic pilot names
		if (strpos($name, "Pilot") !== false) $name = "AI";
		if (strpos($name, "Cougar") !== false) $name = "AI";
		if (strpos($name, "Mastic") !== false) $name = "AI";
		if (strpos($name, "Cyborg") !== false) $name = "AI";
		if (strpos($name, "Cowboy") !== false) $name = "AI";
		if (strpos($name, "Falcon") !== false) $name = "AI";
		if (strpos($name, "Hornet") !== false) $name = "AI";
		if (strpos($name, "Texico") !== false) $name = "AI";
		if (strpos($name, "Ghost") !== false) $name = "AI";
		if (strpos($name, "Diamond") !== false) $name = "AI";
		if (strpos($name, "Panther") !== false) $name = "AI";
		if (strpos($name, "Banshee") !== false) $name = "AI";
		if (strpos($name, "Hornet") !== false) $name = "AI";
		if (strpos($name, "Camel") !== false) $name = "AI";
		if (strpos($name, "Viper") !== false) $name = "AI";
		if (strpos($name, "Chalis") !== false) $name = "AI";
		if (strpos($name, "Nightmare") !== false) $name = "AI";
		if (strpos($name, "Dragon") !== false) $name = "AI";
		if (strpos($name, "Devil") !== false) $name = "AI";
		if (strpos($name, "AWACS") !== false) $name = "AI";
		if (strpos($name, "Unit") !== false) $name = "AI";
		if (strpos($name, "Einheit") !== false) $name = "AI";
					
		return $name;
	}
	
	function filterTypeNames($name) {
		if (strlen($name) <= 0) return $name;
		
		if (strpos($name, "weapons.") !== false) {
			$name = str_replace("weapons.missiles.", "", $name);
			$name = str_replace("weapons.shells.", "", $name);
			$name = str_replace("weapons.rockets.", "", $name);
			$name = str_replace("weapons.bombs.", "", $name);
			
			//weapons
			$name = str_replace("M61_20_", "M61 20 mm ", $name);
			$name = str_replace("DEFA552_30", "DEFA 552 30mm", $name);
			
			$name = str_replace("_", "-", $name);
		} else {
			
			
			//general
			if ($pos = strpos($name, " ")) {
				$name = substr($name, 0, $pos);
			}
		}
		return $name;
	}
}

?>