<?php
/***
    PHPAudio - Audio processing library for PHP.
	Copyright (C) 2015  Amit Sengupta, amit@truelogic.org

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.

**/


namespace phpaudio;

require_once("../clsTools.php");

///////////////////////////////////////////////////////////
class WAV {
	private $mHeader;
	private $mFilePath;
	private $mError = null;


	/**
	 * Constructor method
	 * @param string $filePath Full path to wav file
	 */
	function __construct($filePath='') {

		if ($filePath == null || $filePath == '') {
			$this->mError = "No file specified";
			return;
		}

		// check if file can be read
		try {
			$f = fopen($filePath, "rb");
			if (!$f) {
				$this->mError = "File cannot be read";
				return;
			}
			else {
				fclose($f);
				$this->mFilePath = $filePath;
			}				

		} catch (Exception $ex) {
			$this->$mError = $ex->getMessage();
		}

		$this->parseFile();
	}


	/**
	 * Get last error message
	 */
	function getError() {
		return $this->mError;
	}


	/**
	 * Return header structure
	 */
	function getHeaderData() {
		return $this->mHeader;
	}

	/**
	 * Parse the wav file
	 */
	private function parseFile() {
		$this->mError = null;
		$this->mHeader = new WaveHeader();
	
		$dataSectionWasFound = false;

		try {
			$f = fopen($this->mFilePath, "rb");
			if (!$f) {
				$this->mError = "File cannot be read for parsing";
				return;
			}

			$data = fread($f, 4);
			if (strcmp($data, "RIFF") != 0) {
				fclose($f);
				$this->mError = "RIFF header was not found";
				return;
			}
			$this->mHeader->riff = $data;

			$data = unpack("V", fread($f, 4));
			$this->mHeader->overallSize = $data[1];
			if ((int) $data[1] >= 1024)
				$this->mHeader->overallSizeInKb = (int) $data[1] / 1024;
			if ((int) $data[1] >= (1024*1024))				
				$this->mHeader->overallSizeInMb = (int) $data[1] / (1024 * 1024);


			$data = fread($f, 4);
			if (strcmp($data, "WAVE") != 0) {
				fclose($f);
				$this->mError = "WAVE marker was not found";
				return;
			}
			$this->mHeader->wave = $data;

			$data = fread($f, 4);
			if (strcmp($data, "fmt ") != 0) {
				$this->mError = "Fmt chunk marker was not found";
				return;
			}
			$this->mHeader->fmtChunkMarker = $data;


			$data = unpack("V", fread($f, 4));
			$this->mHeader->lengthOfFmt = $data[1];


			$data = unpack("v", fread($f, 2));
			if ((int) $data[1] == 1)
				$this->mHeader->formatType = "PCM";
			else if ((int) $data[1] == 3)
				$this->mHeader->formatType = "IEEE float";
			else if ((int) $data[1] == 6)
				$this->mHeader->formatType = "A-Law";
			else if ((int) $data[1] == 7)
				$this->mHeader->formatType = "mu-Law";


			$data = unpack("v", fread($f, 2));
			$this->mHeader->channels = (int) $data[1];


			$data = unpack("V", fread($f, 4));
			$this->mHeader->sampleRate = (int) $data[1];

			$data = unpack("V", fread($f, 4));
			$this->mHeader->byteRate = (int) $data[1];

			$data = unpack("v", fread($f, 2));
			$this->mHeader->blockAlign = (int) $data[1];

			$data = unpack("v", fread($f, 2));
			$this->mHeader->bitsPerSample = (int) $data[1];
		
			// check for non-PCM header (alaw or mulaw)
			if ((int) $this->mHeader->lengthOfFmt > 16) {
					$data = unpack("v", fread($f, 2));
					$this->mHeader->extendedSize = (int) $data[1];

					$data = fread($f, 4);
					if (strcmp($data, "data") == 0) {
						$dataSectionWasFound  = true;
					} else {
					
						if (strcmp($data, "fact") != 0) {
							fclose($f);
							$this->mError = "Fact chunk marker expected but not found";
							return;
						}
						$this->mHeader->fact = $data[1];

						$data = unpack("V", fread($f, 4));
						$this->mHeader->factChunkSize = (int) $data[1];

						$data = unpack("V", fread($f, 4));
						$this->mHeader->factSampleLength = (int) $data[1];
					} // 	if (strcmp($data, "data") != 0)  else

			}

			// check for ieee floating point type header
			else if ($this->mHeader->formatType = "IEEE float") {
					$data = fread($f, 4);
					// the "data" section may not start immediately if there is a "fact" or "FLLR" section
					if (strcmp($data, "data") == 0) {
						$dataSectionWasFound = true;

					} else 
					//check for Apple encoded (RIFF compliant) FLLR data marker)
					if (strcmp($data, "FLLR") == 0) {
						// loop fwd 4 bytes till we find "data" or we reach eof
						$found = false;
						while (!$found) {
							$data = fread($f, 4);
							if (feof($f)) {
								break;
							}
							if (strcmp($data, "data") == 0) {
								$found = true;
								break;
							}
						} // while !$found
						if (!$found) {
							fclose($f);
							$this->mError = "Data chunk marker was not found";
							return;
						} else {
							$dataSectionWasFound = true;
						}							

					} else {
						if (strcmp($data, "fact") != 0) {
							fclose($f);
							$this->mError = "Fact chunk marker expected but not found";
							return;
						}
						$this->mHeader->fact = $data[1];

						$data = unpack("V", fread($f, 4));
						$this->mHeader->factChunkSize = (int) $data[1];

						$data = unpack("V", fread($f, 4));
						$this->mHeader->factSampleLength = (int) $data[1];

						//there may be a PEAK header , so we need to loop over bytes till we come to data section
						$found = false;
						while (!$found) {
							$data = fread($f, 4);
							if (feof($f)) {
								break;
							}
							if (strcmp($data, "data") == 0) {
								$found = true;
								break;
							}							
						}
						if ($found) {
							$dataSectionWasFound = true;
						}
					} // 	if (strcmp($data, "data") == 0)  else 


			} // 	else if ($this->mHeader->formatType = "IEEE float") 



			if (!$dataSectionWasFound)
				$data = fread($f, 4);

			if (strcmp($data, "data") != 0) {

				// check for Apple encoded (RIFF compliant) FLLR data marker)
				if (strcmp($data, "FLLR") == 0) {
						// loop fwd 4 bytes till we find "data" or we reach eof
						$found = false;
						while (!$found) {
							$data = fread($f, 4);
							if (feof($f)) {
								break;
							}
							if (strcmp($data, "data") == 0) {
								$found = true;
								break;
							}
						} // while !$found
						if (!$found) {
							fclose($f);
							$this->mError = "Data chunk marker was not found";
							return;
						}

				}
				else {
					fclose($f);
					$this->mError = "Data chunk marker was not found";
					return;
				}					
			}
			$this->mHeader->dataChunkHeader = $data;

			$data = unpack("V", fread($f, 4));
			$this->mHeader->dataSize = (int) $data[1];

		
			fclose($f);

			// calculate rest of the stuff
			$this->mHeader->numberOfSamples = (8 * (int) $this->mHeader->dataSize) / ((int) $this->mHeader->channels * (int) $this->mHeader->bitsPerSample);
			$this->mHeader->sizeOfEachSample = ((int) $this->mHeader->channels * (int) $this->mHeader->bitsPerSample) / 8;
			$this->mHeader->durationInSeconds = (int) $this->mHeader->overallSize / (float) ($this->mHeader->byteRate);
			$this->mHeader->bytesInEachChannel = (int) $this->mHeader->sizeOfEachSample / (int) $this->mHeader->channels;

			$this->mHeader->durationInHMS = Tools::secondsToHMS($this->mHeader->durationInSeconds);
	
		
		} catch (Exception $ex) {
			$this->mError = $ex->getMessage();
			return;
		}
	}
}

//////////////////////////////////////////////////////////

// header struct of a WAV file

class WaveHeader {
	
	var $riff = "";										/* "RIFF" string marker */
	var $overallSize = 0;								/*overall size of file in bytes*/
	var $overallSizeInKb = 0;
	var $overallSizeInMb = 0;
	var $wave = "";										/* "WAVE" string marker */
	var $fmtChunkMarker = "";							/* "fmt" string marker */
	var $lengthOfFmt = 0;								/* length of header in bytes */
	var $formatType = "";								/* 1 - PCM , 3- IEEE floating point, 6 - A-Law, 7 - mu-Law */
	var $channels = 0;									/* no.of channels */
	var $sampleRate = 0;								/* sampling rate*/
	var $byteRate = 0;									/* byte rate */
	var $blockAlign = 0;								/* bytes for alignment */
	var $bitsPerSample = 0;								/* how many bits required for each sample */

	var $extendedSize = 0;								/* only found in non PCM or log-PCM headers*/

	var $fact = "";										/* will contain "fact" for log-PCM headers */
	var $factChunkSize = 0;								/* size of fact chunk . normally 4 */
	var $factSampleLength = 0;							/* sample length = channels * block size . Same as dataSize in PCM header*/
	
	var $validBitsPerSample = 0;						/* only found in non PCM header*/
	var $channelMask = 0;								/* only found in non PCM header*/
	var $subFormat = 0;									/* only found in non PCM header*/

	var $dataChunkHeader = 0;							/* "data" string marker */
	var $dataSize = 0;									/* size of data chunk */
														/* the data below is calculated*/ 
	var $numberOfSamples = 0;
	var $sizeOfEachSample = 0;
	var $durationInSeconds = 0;
	var $durationInHMS = "";
	var $bytesInEachChannel = 0;
}

?>
