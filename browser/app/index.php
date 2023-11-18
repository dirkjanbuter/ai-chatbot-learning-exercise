<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('max_execution_time', '1000');
ini_set('default_charset', 'UTF-8');
ini_set('memory_limit', '256G');

$data = [
'stopword' => ['stopwords.dat', [], 1],
'malename' => ['malenames.dat', [], 2],
'femalename' => ['femalenames.dat', [], 2],
'unisexname' => ['unisexnames.dat', [], 2],
'companyname' => ['companynames.dat', [],3],
'number' => ['numbers.dat', [], 4],
'day' => ['days.dat', [], 5],
'month' => ['months.dat', [], 6],
'continent' => ['continents.dat', [], 7],
'city' => ['cities.dat', [], 8],
'country' => ['countries.dat', [], 9],
'state' => ['states.dat', [], 10],
'subregion' => ['subregions.dat', [], 11],
'pronoun' => ['pronouns.dat', [], 12],
'adverb' => ['adverbs.dat', [], 13],
'article' => ['articles.dat', [], 14],
'preposition' => ['prepositions.dat', [], 15],
'interjection' => ['interjections.dat', [], 16],
'irregularverb' => ['irregularverbs.dat', [], 17],
'verb' => ['verbs.dat', [], 18],
'noun' => ['nouns.dat', [], 19],
];

foreach($data as $type => $val)
{
	if (!($fp = fopen('../data/datfiles/'.$val[0], 'r')))
	{
		echo 'Error: Opening data file \''.$val[0].'\' feiled!';
	}
	else
	{
		do
		{
			$binarydata = fread($fp, 40);
			if($binarydata) {
				$key = unpack('P', $binarydata, 0)[1];
				$value = strtolower(trim(unpack('a32', $binarydata, 8)[1]));
				$data[$type][1][$value] = $key;
			}
		} while(!feof($fp));
		fclose($fp);	
	}
}

$url = isset($_GET['url'])?$_GET['url']:'https://dirkjanbuter.github.io/magazine/en/welcome-and-open-a-new-world-and-discover-its-secrets.html';

$parts = parse_url($url);
$scheme = $parts['scheme'] ?? 'https';
$host = $parts['host'] ?? 'localhost';
$path = $parts['path'] ?? '/';
$query = isset($parts['query'])?('?'.$parts['query']):'';
$fragment = isset($parts['fragment'])?('#'.$parts['fragment']):'';
$root = mb_substr($path, 0, strrpos($path, '/') + 1);
$ip = gethostbyname($host);


if(!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE))
{
    $host = 'dirkjanbuter.com'; 
}
$blacklist = array(
    '127.0.0.1',
    '::1'
);

if(in_array(trim($ip), $blacklist)){
    $host = 'dirkjanbuter.com'; 
}

$html = '';
$id = md5($scheme.'://'.$host.$path).'_'.preg_replace('/[^\da-z]/i', '-', $scheme.'://'.$host.$path);
$file = '../data/cache/'.$id.'.html';
if(file_exists($file))
{
	$html = file_get_contents($file);
}
else
{
	$html = file_get_contents($scheme.'://'.$host.$path.$query.$fragment);
	file_put_contents($file, $html);
}
$html = mb_convert_encoding($html, 'HTML-ENTITIES', "UTF-8");

$doc = new DOMDocument();
$doc->loadHTML($html, LIBXML_NOWARNING | LIBXML_NOERROR);


function tokenize($text)
{
	// Tokens
	return preg_replace_callback('/([a-zA-ZàèìòùÀÈÌÒÙáéíóúýÁÉÍÓÚÝâêîôûÂÊÎÔÛãñõÃÑÕäëïöüÿÄËÏÖÜŸåÅæÆœŒçÇðÐøØßˈɛə]+(\'s|\'t|’s|’t)?|[0-9\-\–\/]+|[\.\,\;\?\!\:\"“”\'’¡¿\/]|[\s]+|[\-\–]+)/u', function ($matches) 
	{            
		global $data;
		$rawToken = trim($matches[1]);
		$token = strtolower($rawToken);

		if(is_numeric($rawToken))
		{
			return '<span class="word-number"title="Number">'.$matches[1].'</span>';
		}
		else
		{
			switch($matches[1])
			{
				case ' ': return '<span class="word-space" title="Space"> </span>'; break;
				case '.': return '<span class="word-interpunction" title="Interpunction">.</span>'; break;
				case ',': return '<span class="word-interpunction" title="Interpunction">,</span>'; break;
				case ';': return '<span class="word-interpunction" title="Interpunction">;</span>'; break;
				case '?': return '<span class="word-interpunction" title="Interpunction">?</span>'; break;
				case '!': return '<span class="word-interpunction" title="Interpunction">!</span>'; break;
				case ':': return '<span class="word-interpunction" title="Interpunction">:</span>'; break;
				case '“':
				case '”':
				case '"': return '<span class="word-interpunction" title="Interpunction">"</span>'; break;
				case '\'':
				case '’': return '<span class="word-interpunction" title="Interpunction">\'</span>'; break;
				case '/': return '<span class="word-interpunction" title="Interpunction">/</span>'; break;
				case '¿': return '<span class="word-interpunction" title="Interpunction">¿</span>'; break;
				case '¡': return '<span class="word-interpunction" title="Interpunction">¡</span>'; break;
				case '-': 
				case '–': return '<span class="word-interpunction" title="Interpunction">-</span>'; break;
				default: 
				{
					foreach($data as $type => $val)
					{
						$checks = [];
						switch($type)
						{
							case 'day':
							case 'month':
							case 'firstname':
							case 'malename':
							case 'femalename':
							case 'unisexname':
							case 'companyname':
							case 'continent':
							case 'country':
							case 'subregion':
							case 'state':
							case 'city':
							{
								if(preg_match('/^[A-Z]/', $rawToken) === 1)
								{
									$checks[] = $token;
									if(mb_substr($token, -2) == '\'s')
									{
										 $checks[] = mb_substr($token, 0, -2);
									}															
									if(mb_substr($token, -2) == '’s')
									{
										 $checks[] = mb_substr($token, 0, -2);
									}															
								}
							} break;							
							case 'noun': 
							{
								$checks[] = $token;
								if(mb_substr($token, -1) == 's')
									 $checks[] = mb_substr($token, 0, -1);
								if(mb_substr($token, -3) == 'ies')
									 $checks[] = mb_substr($token, 0, -3).'y';
								if(mb_substr($token, -3) == 'y\'s')
									 $checks[] = mb_substr($token, 0, -2);
								if(mb_substr($token, -3) == 'y’s')
									 $checks[] = mb_substr($token, 0, -2);
									 
							} break;
							case 'verb': 
							{
								$checks[] = $token;
								if(mb_substr($token, -4) == 'nned')
								{
									 $checks[] = mb_substr($token, 0, -3);
								}
								if(mb_substr($token, -2) == 'ed')
								{
									 $checks[] = mb_substr($token, 0, -1);
									 $checks[] = mb_substr($token, 0, -2);
								}
								if(mb_substr($token, -3) == 'ing')
								{
									 $checks[] = mb_substr($token, 0, -3);
									 $checks[] = mb_substr($token, 0, -3).'e';
								}
								if(mb_substr($token, -3) == 'ied')
								{
									 $checks[] = mb_substr($token, 0, -3).'y';
								}								
							} break;
							case 'adverb':
							{
								$checks[] = $token;
								if(mb_substr($token, -3) == 'lly')
								{
									 $checks[] = mb_substr($token, 0, -2);
								}															
							} break;
							default:
							{
								$checks[] = $token;
							}
							
						}
						foreach($checks as $check) 
						{
							if(array_key_exists($check, $val[1]))
							{
								return '<span class="word-'.$type.'" title="'.ucfirst($type).'">'.$rawToken.'</span>';
							}
						}	
						
					}
					return '<span class="word-unknown" title="Unknown word type">'.$rawToken.'</span>';
				}
			}
		}
	}, $text);
}

function sentence($text)
{
	$html = '';
	preg_match_all("/([^\.\!\?]+[\.\!\?])\s/u", $text, $matches);
	foreach($matches[1] as $match)
	{
		$html .= '<span class="sentence">'.tokenize($match).'</span>';
	}
	return $html;
}



$html = '';
$id = md5($scheme.'://'.$host.$path).'_'.preg_replace('/[^\da-z]/i', '-', $scheme.'://'.$host.$path);
$file = '../data/cache/'.$id.'.cch';


$exists = false;
if(file_exists($file))
{
	if(isset($_GET['img']))
	{  
		header('Content-Type: image/png');
		readfile('../shared/images/exists.png');
		exit;
	}
	$exists = true;
}



$links = [];

function setLinkUrl($source, $title)
{
	global $links, $parts;
	
	$linkParts = parse_url($source);
	$scheme = $linkParts['scheme'] ?? $parts['scheme'] ?? 'https';
	$host = $linkParts['host'] ?? $parts['host'] ?? 'localhost';
	if(!isset($linkParts['path']) || empty($linkParts['path']))
	{
		$path = '/';	
	}	
	elseif(substr($linkParts['path'], 0, 1) != '/')
	{
		$path = substr($parts['path'], 0, strrpos($parts['path'], '/')+1).$linkParts['path'];
	}
	else
	{
		$path = $linkParts['path'] ?? $parts['path'] ?? '/';
	}
	$query = isset($linkParts['query'])?('?'.$linkParts['query']):'';
	$fragment = isset($linkParts['fragment'])?('#'.$linkParts['fragment']):'';
	$root = mb_substr($path, 0, strrpos($path, '/') + 1);
	$ip = gethostbyname($host);


	if(!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE))
	{
	    return;
	}
	$blacklist = array(
	    '127.0.0.1',
	    '::1'
	);	
	if(in_array(trim($ip), $blacklist)){
	    return;
	}
	if($scheme !== 'http' && $scheme !== 'https')
	{
		return;
	}
	$linkUrl = $scheme.'://'.$host.$path.$query.$fragment;
	$link['source'] = $linkUrl;
	$link['id'] = md5($linkUrl .':' . 'magic');
	$link['title'] = $title;
	$links[] = $link;
}

/*
function setLinkUrl($source, $title)
{
	global $links, $parts;
	
	$linkParts = parse_url($source);
	$scheme = $linkParts['scheme'] ?? $parts['scheme'] ?? 'https';
	$host = $linkParts['host'] ?? $parts['host'] ?? 'localhost';
	$path = $linkParts['path'] ?? $parts['path'] ?? '/';
	$root = mb_substr($path, 0, strrpos($path, '/') + 1);
	$ip = gethostbyname($host);


	if(!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE))
	{
	    return;
	}
	$blacklist = array(
	    '127.0.0.1',
	    '::1'
	);	
	if(in_array(trim($ip), $blacklist)){
	    return;
	}
	if($scheme !== 'http' && $scheme !== 'https')
	{
		return;
	}
	$linkUrl = $scheme.'://'.$host.$path;
	
	$link['source'] = $linkUrl;
	$link['id'] = md5($linkUrl .':' . 'magic');
	$link['title'] = $title;
	$links[] = $link;
}*/

function showDOMNode(DOMNode $domNode, $html) 
{

    foreach ($domNode->childNodes as $node)
    {
	switch($node->nodeName)
	{
		case 'h1': 
		case 'h2': 
		case 'h3': 
		case 'h4':
		case 'h5':
		case 'h6':
		case 'h7':
		case 'h8': 
		{
			$html .= '<'.$node->nodeName.'>'.tokenize($node->textContent).'</'.$node->nodeName.'>'; 
			foreach ($node->childNodes as $subnode)
    			{
   				if($subnode->nodeName !== 'a')
    					continue;
				setLinkUrl($subnode->getAttribute('href'), $subnode->textContent);
			}	
		} break;
		case 'p': 
		{
			if(trim($node->nodeValue) !== '')
			{
				$text = $node->ownerDocument->saveHTML($node);
				$text = preg_replace('/<\s*(style|script).+?<\s*\/\s*(style|script).*?>|\[[0-9^\]]+\]|\[[a-zA-Z^\]]\]/iu', '', $text);
				$text = strip_tags($text, '<br>');
				$paragraph = sentence($text);
				if(trim($paragraph) !== '')
				{
					$html .= '<p>';
					$html .= $paragraph;
					$html .= '</p>'; 
				}
			}
			
			foreach ($node->childNodes as $subnode)
    			{
    			
    			    	if($subnode->nodeName !== 'a')
    					continue;
				setLinkUrl($subnode->getAttribute('href'), $subnode->textContent);
			}			
		} break;
		case 'a': 
		{
			setLinkUrl($node->getAttribute('href'), $node->textContent);
		} break;
		default:
		{
		        if($node->hasChildNodes()) 
		        {
		            $html = showDOMNode($node, $html);
		        }
		}

	}
    }
    return $html;    
}

/*
function showDOMNode(DOMNode $domNode, $html) 
{
    foreach ($domNode->childNodes as $node)
    {
	switch($node->nodeName)
	{
		case 'h1': 
		case 'h2': 
		case 'h3': 
		case 'h4':
		case 'h5':
		case 'h6':
		case 'h7':
		case 'h8': 
		$html .= '<'.$node->nodeName.'>'.tokenize($node->textContent).'</'.$node->nodeName.'>'; break;
		case 'p': {
			if(trim($node->nodeValue) !== '')
			{
				$text = $node->ownerDocument->saveHTML($node);
				$text = preg_replace('/<\s*(style|script).+?<\s*\/\s*(style|script).*?>|\[[0-9^\]]+\]|\[[a-zA-Z^\]]\]/iu', '', $text);
				$text = strip_tags($text, '<br>');
				$paragraph = sentence($text);
				if(trim($paragraph) !== '')
				{
					$html .= '<p>';
					$html .= $paragraph;
					$html .= '</p>'; 
				}
			}
		} break;
		//case 'a': echo $node->textContent.' '; break;
		default:
		{
		        if($node->hasChildNodes()) 
		        {
		            $html = showDOMNode($node, $html);
		        }
		}

	}
    }
    return $html;    
}*/
//$html = showDOMNode($doc, '');




if(!$exists)
{
	$html = file_get_contents($scheme.'://'.$host.$path);

	$html = mb_convert_encoding($html, 'HTML-ENTITIES', "UTF-8");

	$doc = new DOMDocument();
	$doc->loadHTML($html, LIBXML_NOWARNING | LIBXML_NOERROR);

	$html = showDOMNode($doc, '');
	
	file_put_contents('../data/cache/'.$id.'.cch', $url."\r\n".$html);

	// Create links file
	$i = 0;
	if (!$fp = fopen('../data/cache/'.$id.'.lns', 'w')) 
	{
		echo "Cannot open file ($filename)";
		return false;
	}
		

	foreach($links as $link)
	{
		$binarydata = pack('Pa2083a256', $i+1, $link['source'], $link['title']);
		fwrite($fp, $binarydata);
		$i++;
	}

	fclose($fp);
}
else
{
	$html = file_get_contents($file);
	$html = substr($html, strpos($html, "\n")+1);



	if (!$fp = fopen('../data/cache/'.$id.'.lns', 'r')) 
	{
		echo "Cannot open file ($filename)";
		return false;
	}
		

	do
	{
		$binarydata = fread($fp, 2347);
		if($binarydata) {
			$link = [];
			$key = unpack('P', $binarydata, 0)[1];
			$link['source'] = trim(unpack('a2083', $binarydata, 8)[1]);
			$link['title'] = trim(unpack('a256', $binarydata, 2081)[1]);
			$links[$key] = $link;
		}
	} while(!feof($fp));

	fclose($fp);

}




if(isset($_GET['img']))
{  
	header('Content-Type: image/png');
	readfile('../shared/images/succeeded.png');
	exit;
}
else
{
	echo '<!DOCTYPE html>';
	echo '<html>';
	echo '<head>';
	echo '<meta charset="UTF-8">';
	echo '<meta name="robots" content="noindex, nofollow">';
	echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
	echo '<title>NLP Browser</title>';
	echo '<link rel="stylesheet" href="../shared/styles/nlp.css">';
	echo '</head>';
	echo '<body>';

	echo '<form method="get" action="index.php">';
	echo '<input class="address" type="text" name="url" value="'.htmlspecialchars($scheme.'://'.$host.$path).'"/>';
	echo '<input type="submit" value="GO!">';
	echo '</form>';

	echo '<div class="page">';
	echo $html;
	echo '</div>';

	echo '<div class="links">';
			foreach($links as $link)
			{
			 	echo '<img src="?img=1&url='.urlencode($link['source']).'" width="12" height="12"> ' . htmlspecialchars($link['title']).' (<a href="?url='.urlencode($link['source']).'">'.htmlspecialchars($link['source']).'</a>)<br>';
			}
	echo '</div>';

	echo '</body>';

	echo '</html>';
}



