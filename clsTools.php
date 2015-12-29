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

////////////////////////////////////////////////////////////
class Tools {

	/**
	 * Convert seconds to hh:mm:ss string. Does rounding.
	 * @param int $seconds
	 * @return string hh:mm:ss string
	 */
	static function secondsToHMS($seconds) {
		$t = round($seconds);
		return sprintf('%02d:%02d:%02d', ($t/3600),($t/60%60), $t%60);
	}	

}
?>
