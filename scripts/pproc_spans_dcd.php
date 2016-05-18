<?php
/*
 * pproc_spans_dcd.php
 *
 * ---------------------------------------------------------------------
 * TODO:
 *  1. Handle 'mirrored' connectives (e.g. if..if..then)
 * ---------------------------------------------------------------------
 *
 * Copyright 2016 Evgeny A. Stepanov <stepanov.evgeny.a@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 *
 * ---------------------------------------------------------------------
 *
 * Post-process output of proc_conll.php for connectives
 *
 * -f output of proc_conll.php
 * -t token-level features file
 * -r character normalization table [optional]
 */
// required or includes
require 'IdMapper.php';
require 'ConllReader.php';
require 'CharNormalizer.php';

// Error & Memory Settings
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit', -1);

// Arguments
$args = getopt('f:t:r:');

// Constants
$sep  = "\t";
$pref = 'RE_';
$otag = 'O';

// Argument Parameters
// Class Initializations
$IDM = new IdMapper(FALSE);
$CFR = new ConllReader();
$CNR = new CharNormalizer();

// Variables

// set character normalization if provided
if (isset($args['r'])) {
	$CNR->readPairs($args['r']);
}
else {
	$CNR->setPairs(array());
}

/*--------------------------------------------------------------------*/
// read tokens
$row_arr = $IDM->arrayFlatten($CFR->conllRead($args['t']));
$doc_arr = $IDM->remap($row_arr, array(0, 2, 3));

// read dcd data: relation per line
$lines   = array_map('trim', file($args['f']));
$dcd_arr = array();
$RID = 0;
foreach ($lines as $line) {
	if ($line != '') {
		$la      = explode($sep, $line);
		$relID   = $pref . $RID;
		$docID   = $la[0];
		$sentID  = $la[2];
		$tid_arr = explode(' ', $la[3]);
		$tag_arr = array_combine($tid_arr, explode(' ', $la[6]));

		$sent    = $doc_arr[$docID][$sentID];
		foreach ($sent as $tokID => $tok) {
			if (in_array($tokID, $tid_arr)) {
				$sent[$tokID] = array_merge(
					array($docID),
					array($relID, $sentID, $tokID),
					array_slice($sent[$tokID], 4, -1),
					array($tag_arr[$tokID])
				);
			}
			else {
				$sent[$tokID] = array_merge(
					array($docID),
					array($relID, $sentID, $tokID),
					array_slice($sent[$tokID], 4, -1),
					array($otag)
				);
			}
		}

		$dcd_arr[$docID][$relID][$sentID] = $sent;
		$RID++;
	}
}
// Printing
foreach ($dcd_arr as $docID => $doc) {
	foreach ($doc as $relID => $rel) {
		foreach ($rel as $sentID => $sent) {
			foreach ($sent as $tokID => $tok) {
				echo implode($sep, $tok) . "\n";
			}
			echo "\n";
		}
	}
}