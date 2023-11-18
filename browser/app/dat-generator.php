<?php
exit;
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('max_execution_time', '1000');
ini_set('default_charset', 'UTF-8');
set_time_limit(180);
ini_set('memory_limit', '256G');


require('array-months.php');
require('array-days.php');
require('array-numbers.php');
require('array-stopwords.php');
require('array-irregularverbs.php');
require('array-companynames.php');
require('array-firstnames.php');
require('array-countries.php');
require('array-pronouns.php');
require('array-continents.php');


$umlautsUnicode =  [
       "256" => "<A/>",
       "257" =>  "<a/>",
       "258" =>  "<A^>",
       "258" =>  "<Â>",   /** <A^> **/
       "259" =>  "<a^>",
       "259" =>  "<â>",   /** <a^> **/
       "260" =>  "<A,>",
       "261" =>  "<a,>",
       "262" =>  "<CŽ>",
       "263" =>  "<cŽ>",
       "268" =>  "<C^>",
       "268" =>  "<CH>",
       "269" =>  "<c^>",
       "269" =>  "<ch>",
       "271" =>  "<dŽ>",
       "272" =>  "<Ð>",
       "272" =>  "<DJ>",
       "273" =>  "<ð>",
       "273" =>  "<dj>",
       "274" =>  "<E/>",
       "275" =>  "<e/>",
       "278" =>  "<E°>",
       "279" =>  "<e°>",
       "280" =>  "<E,>",
       "281" =>  "<e,>",
       "282" =>  "<E^>",
       "282" =>  "<Ê>",
       "283" =>  "<e^>",
       "283" =>  "<ê>",
       "286" =>  "<G^>",
       "287" =>  "<g^>",
       "290" =>  "<G,>",
       "291" =>  "<gŽ>",
       "298" =>  "<I/>",
       "299" =>  "<i/>",
       "304" =>  "<I°>",
       "305" =>  "<i>",
       "306" =>  "<IJ>",
       "307" =>  "<ij>",
       "310" =>  "<K,>",
       "311" =>  "<k,>",
       "315" => "<L,>",
       "316" =>  "<l,>",
       "317" =>  "<LŽ>",
       "318" =>  "<lŽ>",
       "321" =>  "<L/>",
       "322" =>  "<l/>",
       "325" =>  "<N,>",
       "326" =>  "<n,>",
       "327" =>  "<N^>",
       "328" =>  "<n^>",
       "336" =>  "<Ö>",
       "337" =>  "<ö>",
       "338" =>  "<OE>",
       "338" =>  "",   /** <OE> **/
       "339" =>  "<oe>",
       "339" =>  "",   /** <oe> **/
       "344" =>  "<R^>",
       "345" =>  "<r^>",
       "350" =>  "<S,>",
       "351" =>  "<s,>",
       "352" =>  "<S^>",
       "352" =>  "",   /** <S^> **/
       "352" =>  "<SCH>",
       "352" =>  "<SH>",
       "353" =>  "<s^>",
       "353" =>  "",   /** <s^> **/
       "353" =>  "<sch>",
       "353" =>  "<sh>",
       "354" =>  "<T,>",
       "355" =>  "<t,>",
       "357" =>  "<tŽ>",
       "362" =>  "<U/>",
       "363" =>  "<u/>",
       "366" =>  "<U°>",
       "367" =>  "<u°>",
       "370" =>  "<U,>",
       "371" =>  "<u,>",
       "379" =>  "<Z°>",
       "380" =>  "<z°>",
       "381" =>  "<Z^>",
       "382" =>  "<z^>",
       "7838" => "<ß>",   /***  newly defined "Großes ß"  ***/
];

function namDictToData($filename, $gender)
{
	global $data, $umlautsUnicode;

	$i = 0;
	$fp = null;
	if (!($fp = fopen($filename, 'w'))) 
	{
		echo "Cannot open file ($filename)";
		return false;
	}
		
		
	$lines = file('../../data/nam_dict/names.txt');
	
	 
	foreach($lines as $line) 
	{
		if(preg_match('/^([^\s]*)[\s]+([^\s]*)/u', $line, $matches) !== false)
		{
			if($gender = $matches[1] || ($gender = '?' && $matches[1] != 'M' && $matches[1] != 'F'))
			{		
				$name = mb_ereg_replace_callback(
				      "<[^>]*>",
				      function ($matches) {
					 global $umlautsUnicode;
					 return mb_chr((int)array_search($matches[0], $umlautsUnicode));
				      },
				      $matches[2]);
				
				$name = mb_ereg_replace_callback('[\+]', function($m) {
					return ' ';
				}, $name);			

				$binarydata = pack('Pa32', $i+1, $name);
				fwrite($fp, $binarydata);
				$i++;			
			}	
		}
	}

	fclose($fp);

	return true;
}



function dr5hnToData($filename, $type)
{
	$i = 0;
	if (!$fp = fopen($filename, 'w')) {
		echo "Cannot open file ($filename)";
		return false;
	}
		
	$data = @json_decode(@file_get_contents('../../data/dr5hn/'.$type.'.json'), true);
	foreach($data ?? [] as $key => $val)
	{
		if(isset($val['name'])) 
		{
			$binarydata = pack('Pa32', $i+1, $val['name']);
			fwrite($fp, $binarydata);
			$i++;
		}
	}
	
	fclose($fp);
	
	return true;
}



function wordsetToData($filename, $type)
{
	$i = 0;
	if (!$fp = fopen($filename, 'w')) {
		echo "Cannot open file ($filename)";
		return false;
	}
	
	
	// loop trough filss
	$files = scandir('../../data/wordset/');
	foreach($files as $file)
	{	
		$data = @json_decode(@file_get_contents('../../data/wordset/'.$file), true);
		foreach($data ?? [] as $key => $val)
		{
			
			if(isset($val['meanings'])) 
			{
				$wordTypes = [];
				foreach($val['meanings'] as $meaning)
				{
					$wordTypes[] = $meaning["speech_part"];
				}
				$wordTypes = array_unique($wordTypes);
			}
			if(in_array($type, $wordTypes))
			{				
				$binarydata = pack('Pa32', $i+1, $key);
				fwrite($fp, $binarydata);
				$i++;
			}
		}
	}
	
	fclose($fp);
	
	return true;
}


function arrayToData($filename, $words)
{
	$i = 0;
	if (!$fp = fopen($filename, 'w')) {
		echo "Cannot open file ($filename)";
		return false;
	}
	
	foreach($words as $word)
	{	
		$binarydata = pack('Pa32', $i+1, $word);
		fwrite($fp, $binarydata);
		$i++;
	}
	
	fclose($fp);
	
	return true;
}



dr5hnToData('cities.dat', 'cities');
dr5hnToData('countries.dat', 'countries');
dr5hnToData('states.dat', 'states');
dr5hnToData('subregions.dat', 'subregions');
wordsetToData('verbs.dat', 'verb');
wordsetToData('adverbs.dat', 'adverb');
wordsetToData('nouns.dat', 'noun');
wordsetToData('articles.dat', 'article');
wordsetToData('prepositions.dat', 'preposition');
wordsetToData('interjections.dat', 'interjection');
arrayToData('companynames.dat', $companyNames);
arrayToData('stopwords.dat', $stopWords);
arrayToData('irregularverbs.dat', $irregularVerbs);
arrayToData('numbers.dat', $numbers);
arrayToData('days.dat', $days);
arrayToData('months.dat', $months);
arrayToData('firstnames.dat', $firstnames);
arrayToData('pronouns.dat', $pronouns);
arrayToData('continents.dat', $continents);

namDictToData("malenames.dat", 'M');
namDictToData("femalenames.dat", 'F');
namDictToData("unisexnames.dat", '?');


