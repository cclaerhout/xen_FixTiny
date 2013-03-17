<?php
class Sedo_FixTiny_ViewPublic_Editor_ToHtml extends XFCP_Sedo_FixTiny_ViewPublic_Editor_ToHtml
{
	private $debug_pollution = false;
	private $debug_WrappedList = false;

	private $guiltyTags;
	private $RegexCreatePollution;
	private $RegexMatchWrappedList;
	private $datas;
	private $activeTag;
	private $depth = 0;
	private $process = false;

	public function renderJson()
	{
		//Get Content in Bb Codes
		$bbCodeContent = $this->_params['bbCode'];

		//Create Tag pollution (only if code wrapping with guilty tags)...
		$this->InitCreateTagPollution();
		$bbCodeContent = $this->CreateTagPollution($bbCodeContent);

		if($this->debug_pollution === true)
		{
			Zend_Debug::dump($bbCodeContent);
			break;
		}

		//Fix Wrapped List
		$bbCodeContent = preg_replace_callback($this->RegexMatchWrappedList, array($this, 'fixWrappedList'), $bbCodeContent);

		//Modify Bb Codes Text
		$this->_params['bbCode'] = $bbCodeContent;

		//Active HTML Parser from parent function
		$parent = parent::renderJson();

		//Get back real HTML, PHP, CODE & QUOTE Bb Codes if they have been modified
		$parent = $this->getBackWrappedTags($parent);


		return $parent;
	}

	private function InitCreateTagPollution()
	{
      		//Bake guilty tags
		$options = XenForo_Application::get('options');

      		$bakeGuiltyTags = explode(',', $options->tinymce_fix_tagspolution_tags);
		$guiltyTags = array();

      		foreach($bakeGuiltyTags as $key => $tag)
      		{
      			if(!empty($tag))
      			{
				$guiltyTags['opening_regex'][] = '\[' . $tag . '(?:=.+?)?\]';
				$guiltyTags['naked'][] = $tag;
				$guiltyTags['opening'][] = '[' . $tag . ']';
				$guiltyTags['closing'][] = '[\/' . $tag . ']';
				$guiltyTags['closing_regex'][] = '\[\/' . $tag . '\]';
      			}
      		}

      		$this->guiltyTags['opening_regex'] = implode('|', $guiltyTags['opening_regex']);
      		$this->guiltyTags['naked'] = implode('|', $guiltyTags['naked']);
      		$this->guiltyTags['opening'] = implode('|', $guiltyTags['opening']);
      		$this->guiltyTags['closing'] = implode('|', $guiltyTags['closing']);
      		$this->guiltyTags['closing_regex'] = implode('|', $guiltyTags['closing_regex']);

		$otags = $this->guiltyTags['opening_regex'];
		$ctags = $this->guiltyTags['closing_regex'];

		$this->RegexCreatePollution =	'/(?x)						#active regex comments
			(?P<beginTags>(?:(?:' . $otags . '))+)					#capture beginTags group => must be GuiltyTags (repeat option)
			(?!(?:(?:' . $otags . ')))						#the beginTags group must be followed by a "normal" tag (which is not a GuiltyTag)
			(?:.*)?									#Edit above line... can have some text between beginTags group and "normal" tag (experimental - delete line if problem)
			\[(?!\/)(?P<tag>(?!' . $this->guiltyTags['naked'] . '\]).+?)\]		#the normal tag must be an opening tag (capture naked tag) and NOT a guilty tags 
			.*?									#go to...
			(?P<endingTags>(?:\[\/.+?\])+)?$					#... the end and if match a endingsTags group, proceed to the capture
			/iu';									//Options: case insensitive + unicode

		$this->RegexMatchWrappedList = '/(?x)						#active regex comments
			(?P<beginTags>(?:(?:' . $otags . '))+)					#capture beginTags group => must be GuiltyTags (repeat option)
			(?P<List>								#capture list starts...
			\s*									#... but firt capture and permit any white spaces before
			\[list(?:=.*?)?\]							#list opening tag is here (options supported)
			[\s\S]+?								#match any caracters (even white spaces such as carriage return) until...
			\[\/list\]								#list closing tag
			\s*									#capture and permit any white spaces after
			)									#capture list ends
			(?P<endingTags>(?:(?:' . $ctags . '))+)					#capture closingTags
			/ui';									//Options: case insensitive + unicode
	}

	/******
	*	Parent function to create Tag Pollution (Level: Parent)
	*	It will init the Regex Line by line mode (m)
	**/
	private function CreateTagPollution($string)
	{
  		//Line by Line
  		$string = preg_replace_callback('#^.+$#mui', array($this, 'CreateTagPollutionCallback_L1'), $string);

		return $string;
	}

	/******
	*	Meta Pre-Parser line by line (Level 1)
	**/
    	private function CreateTagPollutionCallback_L1($L0)
	{
		$line = $L0[0];

		//Execute Main Pre-Parser
		$line = $this->CreateTagPollutionCallback_L2($line);

		//Avoid TinyMCE breaking for code, php, html & quote Bb codes [The Wysiwyg function of these Bb Codes is useless anyway]
		$line = preg_replace('#\[(/)?(code|php|html|quote)\]#ui', '[$1$2_TinyFix]', $line);

		//Fix for Lists (only those processed and matched with Main Pre-parser)
		$line = $this->fixPollutedBbCodeList($line);

		return $line;
	}

	/******
	*	Main Pre-Parser line by line (Level 2)
	**/
    	private function CreateTagPollutionCallback_L2($line)
	{
		//Check if guilty tags are found before another 'not guilty tag', capture begin group & ending group
		if(preg_match($this->RegexCreatePollution, $line, $matches))
		{
			$tag = $matches['tag'];
			$wrappingTags = $this->Tools_InitWrappingTags($matches);

			//If wrappingTags not null proceed
			if(!empty($wrappingTags))
			{
				$this->process = true;

				//Increment if tags find again
				if(isset($this->activeTag[$this->depth]['tag']) && $tag == $this->activeTag[$this->depth]['tag'])
				{
					$this->depth++;
				}

				$this->activeTag[$this->depth]['tag'] = $tag;
				$this->Tools_CreateWrappingTags($wrappingTags, $this->depth);

				if($this->depth == 0)
				{
					$output = $line . $this->activeTag[$this->depth]['after'];
					if($this->debug_pollution === true)
					{
						$output .= '====== return 1';
					}

					return $output;
				}
				else
				{
					$flattenTOP = $this->Tools_flattenTopLevelGroups($this->depth);
					$output = $flattenTOP['before'] . $line . $this->activeTag[$this->depth]['after'] . $flattenTOP['after'];
					if($this->debug_pollution === true)
					{
						$output .= '====== return 2';
					}
					return $output;
				}
			}

			$flattenTOP = $this->Tools_flattenTopLevelGroups($this->depth);
			$output = $flattenTOP['before'] . $line . $flattenTOP['after'];

			if($this->debug_pollution === true)
			{
				$output .= '====== return 3';
			}
			return $output;
		}
		//Still need to increment and wrap when match an existed tag even if the regex pattern is not matched
		elseif(	isset($this->activeTag[$this->depth]['tag']) && preg_match('#\[' . preg_quote($this->activeTag[$this->depth]['tag'], '#') . '\]#ui', $line) )
		{
			$tag = $this->activeTag[$this->depth]['tag'];
			$this->depth++;
			$this->activeTag[$this->depth]['tag'] = $tag;
			$wrappingTags = '';
			$this->Tools_CreateWrappingTags($wrappingTags, $this->depth);

			$flattenTOP = $this->Tools_flattenTopLevelGroups($this->depth);
			$output = $flattenTOP['before'] . $line . $flattenTOP['after'];


			//check if close tag on the same line
			if(preg_match('#\[/' . preg_quote($tag, '#') . '\]#ui', $line))
			{
				$this->depth--;
			}

			if($this->debug_pollution === true)
			{
				$output .= '====== return 4';
			}

			return $output;
		}


		if($this->process === false)
		{
			return $line;
		}

		//If match closing tag
		if(preg_match('#\[/' . preg_quote($this->activeTag[$this->depth]['tag'], '#') . '\]#iu', $line))
		{
			if($this->depth == 0)
			{
				$output = $this->activeTag[$this->depth]['before'] . $line;
				$this->activeTag[0] = '';
				$this->process = false;

				if($this->debug_pollution === true)
				{
					$output .= '====== return 5';
				}

			}
			else
			{
				$flattenTOP = $this->Tools_flattenTopLevelGroups($this->depth);
				$output = $flattenTOP['before'] . $this->activeTag[$this->depth]['before'] . $line . $flattenTOP['after'];

				unset($this->activeTag[$this->depth]);
				$this->depth--;

				if($this->debug_pollution === true)
				{
					$output .= '====== return 6';
				}
			}

			return $output;
		}
		else
		{
			//Don't repeat twice the same begin/ending tags
			$line = preg_replace('#^' . preg_quote($this->activeTag[$this->depth]['before'], '#') . '#ui', '', $line);
			$line = preg_replace('#' . preg_quote($this->activeTag[$this->depth]['after'], '#') . '$#ui', '', $line);

			if($this->depth == 0)
			{
				$output = $this->activeTag[$this->depth]['before'] . $line . $this->activeTag[$this->depth]['after'];

				if($this->debug_pollution === true)
				{
					$output .= '====== return 7';
				}
			}
			else
			{
				$flattenTOP = $this->Tools_flattenTopLevelGroups($this->depth);
				$output = $flattenTOP['before'] . $this->activeTag[$this->depth]['before'] . $line . $this->activeTag[$this->depth]['after'] . $flattenTOP['after'];

				if($this->debug_pollution === true)
				{
					$output .= '====== return 8';
				}
			}

			return $output;
		}
	}

	private function Tools_InitWrappingTags($matches, $options = null)
	{
		//Get Wrapping tags based on the difference of begin & ending groups
      		$beginTags = $this->Tools_BakeArrayTags($matches['beginTags']);
      		$beginTagWithOptions = $this->Tools_BakeArrayTags($matches['beginTags'], false);

	      	if(isset($matches['endingTags']))
	      	{
	      		$endingTags = $this->Tools_BakeArrayTags($matches['endingTags']);
	      	}
	      	else
	      	{
	      		$endingTags = array();
		}

		if($options == 'intersect')
		{
			$output['array'] = array_intersect($beginTags, $endingTags);
			$output['array']  = $this->getBackOptions($output['array'], $beginTagWithOptions);
			$wip = array('begin' => array(), 'end' => array());

			foreach($output['array'] as $tag)
			{
				if(!empty($tag))
				{
					$wip['begin'][] = '[' . $tag . ']';
					//Delete options
					$tag = preg_replace('#=.+$#ui', '', $tag);
					$wip['end'][] = '[/' . $tag . ']';
				}
			}

			$output['begin'] = implode('', $wip['begin']);
			$output['end'] = implode('',  array_reverse($wip['end']));

			return  $output;
		}

		$output = array_diff($beginTags, $endingTags);
		$output = $this->getBackOptions($output, $beginTagWithOptions);

		//Let's put back tags options in beginTags

		return $output;
	}

    	private function getBackOptions($results, $beginTagWithOptions)
	{
		$diff = array_diff($beginTagWithOptions, $results);

		//If both array are the same no need to waste time
		if(empty($diff))
		{
			return $results;
		}

		//Differences are with options
		$mem = array();
		foreach($diff as $key => $tagWithOptions)
		{
			$nakedTag = preg_replace('#=.+$#ui', '', $tagWithOptions);
			//No tag found
			if(array_search($nakedTag, $results) === false)
			{
				continue;
			}

			//At least 1 key found
			$mem[] = $nakedTag;
			$keys = array_keys($results, $nakedTag);
			$i = count(array_keys($mem, $nakedTag)) - 1;

			$results[$keys[$i]] = $tagWithOptions;
		}

		return $results;
	}


    	private function Tools_BakeArrayTags($datas, $killoptions = true)
	{
		if(empty($datas))
		{
			return array();
		}

		$datas = explode('][', $datas);

		foreach ($datas as &$data)
		{
			//Kill tags
			$data = preg_replace('#[\[\]/]#ui', '', $data);

			//Kill options
			if($killoptions !== false)
			{
				$data = preg_replace('#=.+$#ui', '', $data);
			}
		}

		return $datas;
	}

    	private function Tools_CreateWrappingTags($wrappingTags, $depth)
	{
		if(empty($wrappingTags))
		{
			$this->activeTag[$this->depth]['before'] = '';
			$this->activeTag[$this->depth]['after'] = '';
		}
		else
		{
			foreach($wrappingTags as &$tag)
			{
				$tag = '[' . $tag . ']';
			}

			//before
			$this->activeTag[$this->depth]['before'] = implode('', $wrappingTags);

			//after (need to reverse)
			$wrappingTags = array_reverse($wrappingTags);
			$wip = $this->Tools_KillTagsOptions(implode('', $wrappingTags));
			$wip = str_replace('[', '[/', $wip);
			$this->activeTag[$this->depth]['after'] = $wip;
		}
	}

    	private function Tools_flattenTopLevelGroups($depth)
	{
		if($depth == 0)
		{
			$output['before'] = '';
			$output['after'] = '';

			if(isset($this->activeTag[0]['before']))
			{
				$output['before'] = $this->activeTag[0]['before'];
			}

			if(isset($this->activeTag[0]['after']))
			{
				$output['after'] = $this->activeTag[0]['after'];
			}

			return $output;
		}

		$parts = array_slice($this->activeTag, 0, $depth);

		foreach ($parts as $part)
		{
			$before[] = $part['before'];
			$after[] = $part['after'];
		}

		$output['before'] = implode('', $before);
		$output['after'] = implode('', array_reverse($after));

		return $output;
	}

	private function Tools_KillTagsOptions($string)
	{
		$string = preg_replace('#(\[.+?)=.+?(\])#ui', '$1$2', $string);

		return $string;
	}

	private function fixPollutedBbCodeList($line)
	{
		/*****
		*	# Rules for lists
		*	0) If decoration BbCodes are on the same line than the list tag, then the Pollution Level 2 will have modified the code
		*	1) This tag [*] must be the first of the line (except if there is the list tag of course in the same line of course)
		*	2) All other decoration style must be line by line AFTER the tags [*]
		*
		*	# Example
		*	[center][LIST=1][*][B]zdzadzad[/B]
		*	[*][B]dzadza[/B]
		*	[*][B]dzadzad[/B]
		*	[*][B]zdzad[/B]
		*	[/LIST][/center]
		***/

		if(preg_match('#\[\*\]#ui', $line))
		{
			$line = str_replace('[*]', '', $line);
			$line = '[*]' . $line;
		}

		if(preg_match('#(\[(?:/)?list(?:=.*?)?\])#ui', $line, $match))
		{
			$line = str_replace($match[1], '', $line);
			$line = $match[1] . $line;
		}

		//To do: clean empty guiltyTags

		return $line;
	}

    	private function getBackWrappedTags($string)
	{
		$string = preg_replace('#\[((?:/)?)(\w+?)_TinyFix\]#ui', '[$1$2]', $string);

		return $string;
	}

	private function fixWrappedList($matches)
	{
		/*****
		*	# Example
		*	[center]
		*	[LIST=1][*][B]zdzadzad[/B]
		*	[*][B]dzadza[/B]
		*	[*][B]dzadzad[/B]
		*	[*][B]zdzad[/B]
		*	[/LIST][/center]
		***/

		$wrappingTags = $this->Tools_InitWrappingTags($matches, 'intersect');

		if(empty($wrappingTags['array']))
		{
			//Must have an error, let's the user deals with it
			return $matches['beginTags'] . $matches['List'] . $matches['endingTags'];
		}

		$this->datas['begin'] = $wrappingTags['begin'];
		$this->datas['end'] = $wrappingTags['end'];
		$list = $matches['List'];

		$list = preg_replace_callback('#^.+$#mui', array($this, 'fixWrappedList_L1'), $list);

		if($this->debug_WrappedList === true)
		{
			Zend_Debug::dump($list);
			break;
		}

		return $list;
	}

	private function fixWrappedList_L1($matches)
	{
		$line = $matches[0];
		$line = $this->fixPollutedBbCodeList($line); //Can use this function to be sure 'list' then [*] are first elements of the line

		if(preg_match('#\[\*\]#', $line))
		{
			$line = preg_replace('#(\[\*\])(.+?)$#ui', '$1' . $this->datas['begin'] . '$2' . $this->datas['end'], $line);
		}

		return $line;
	}
}
//Zend_Debug::dump($class);

/**********************************
	REGEX
***********************************
Old:
(?P<beginTags>(?:(?:\[left\]|\[center\]|\[right\]|\[b\]|\[i\]|\[u\]|\[s\]))+)(?!(?:(?:\[left\]|\[center\]|\[right\]|\[b\]|\[i\]|\[u\]|\[s\])))\[(?!/)(?P<tag>.+?)\].*?(?P<endingTags>(?:\[/.+?\])+)?$
New: includes tags options (ex: fonts, color, etc.)

**********************************
	Test patterns
**********************************
[CENTER][b][quote][/b]
aaaa
bbbb
cccc
[/quote][/CENTER]

=>OK but once the message is recorded inside the database if edit again, extra tags will be added (will not affect the message display) *See not at the bottom*

[CENTER][b][quote]
aaaa
bbbb
cccc
[/quote][/b][/CENTER]

=>OK | Saved -> edit OK

[CENTER][quote][/CENTER]
[CENTER]aaaa[/CENTER]
[CENTER]bbbb[/CENTER]
[CENTER]cccc[/CENTER]
[CENTER][/quote][/CENTER]

=>OK | just to check if  regex was not too greedy

[CENTER][B][quote]efezfez
[CENTER][B]efezf[/B][/CENTER]
[LEFT][B]zddzdzd[/B][/LEFT]
dzdzdzd
ezfez[/quote][/B][/CENTER]

=>OK | Saved -> edit OK

[CENTER][B][quote2]aaaaa
[LEFT][B]bbbbb[/B][/LEFT]
cccccc
[right][quote2]eeeeee
ffff[/quote2][/right]
[RIGHT][B]dddddd[/B][/RIGHT]
[/quote2][/B][/CENTER]
=>OK (level 1) | Saved -> edit OK

[CENTER][B][quote2]aaaaa
[LEFT][B]bbbbb[/B][/LEFT]
cccccc
[right][quote2]eeeeee
zorro
ffff[/quote2][/right]
[RIGHT][B]dddddd[/B][/RIGHT]
[/quote2][/B][/CENTER]
=>OK (level 1) | Saved -> edit OK

[CENTER][B][quote2]aaaaa
[LEFT][B]bbbbb[/B][/LEFT]
cccccc
[quote2]eeeeee[/quote2]
[RIGHT][B]dddddd[/B][/RIGHT]
[/quote2][/B][/CENTER]
=>OK (same tag line) | Saved -> edit OK


[CENTER][B][quote2]aaaaa[/B][/CENTER]
[LEFT][B]bbbbb[/B][/LEFT]
cccccc
[quote2]eeeeee[/quote2]
[RIGHT][B]dddddd[/B][/RIGHT]
[center][b][/quote2][/B][/CENTER]
=>OK | Saved -> edit OK

[CENTER][B][quote2]aaaaa[/B][/CENTER]
[LEFT][B]bbbbb[/B][/LEFT]
cccccc
[quote2]eeeeee[/quote2]
[RIGHT][B]dddddd[/B][/RIGHT]
[center][b][/quote2][/B][/CENTER]

ABC
=>OK | Saved -> edit OK

[CENTER][B][quote2]aaaaa
[LEFT][B]bbbbb[/B][/LEFT]
cccccc
[quote2]eeeeee[/quote2]
[RIGHT][B]dddddd[/B][/RIGHT]
[/quote2][/B][/CENTER]
=> OK | Saved -> edit OK

[CENTER][COLOR=#ffcc00][SIZE=5][FONT=arial black][quote2]
aaaa
bbbb
cccc
[/quote2][/FONT][/SIZE][/COLOR][/CENTER]
=> OK | Saved -> edit OK

[CENTER][b]dzadzdzadzad[quote2]aaaaa
[LEFT][B]bbbbb[/B][/LEFT]
cccccc
[RIGHT][B]dddddd[/B][/RIGHT]
[/quote2][/b][/CENTER]
=> OK with experimental mode | Saved -> edit OK

[CENTER][FONT=arial black]dzadzdzadzad[quote2]aaaaa
[LEFT][B]bbbbb[/B][/LEFT]
cccccc
[RIGHT][B]dddddd[/B][/RIGHT]
[/quote2][/FONT][/CENTER]
=> OK with experimental mode | Saved -> edit OK

[CENTER][FONT=arial black]dzadzdzadzad[quote2]aaaaa
[LEFT][B]bbbbb[/B][/LEFT]
cccccc
[IMG]http://www.google.fr/images/srpr/logo3w.png[/IMG]
[RIGHT][B]dddddd[/B][/RIGHT]
[/quote2][/FONT][/CENTER]
=> OK with experimental mode | Saved -> edit OK

[CENTER][FONT=arial black]dzadzdzadzad[quote2]aaaaa
[LEFT][B]bbbbb[/B][/LEFT]
cccccc
[media=youtube]uyln-E0HoEE[/media]
[RIGHT][B]dddddd[/B][/RIGHT]
[/quote2][/FONT][/CENTER]
=> OK with experimental mode | Saved -> edit OK

[CENTER][FONT=arial black][B][quote2][/B][/FONT][/CENTER]
[CENTER][FONT=arial black][B]aaaa[/B][/FONT][/CENTER]
[CENTER][FONT=arial black][B]bbbb[/B][/FONT][/CENTER]
[CENTER][FONT=arial black][B]cccc[/B][/FONT][/CENTER]
[CENTER][FONT=arial black][B][/quote2][/B][/FONT][/CENTER]

[CENTER][FONT=arial black][B][quote2]
aaaa
bbbb
cccc
[/quote2][/B][/FONT][/CENTER]
=>OK bug fixed (options are now getback inside the tag)


BUG: [ FIXED => REGEX TOO GREEDY]

[CENTER][COLOR=#808000][SIZE=5][B]Title[/B][/SIZE][/COLOR][COLOR=#808000][SIZE=5][B] Title complement[/B][/SIZE][/COLOR]

[COLOR=#808000][SIZE=2][B][SIZE=1]Text[/SIZE] Text 2[/B][/SIZE][/COLOR]
[I][SIZE=2][URL='http://www.google.fr']View: http://www.google.fr[/URL][/SIZE][/I][/CENTER]
 
 
this should not be centered but it is...


WORKS
[CENTER][COLOR=#808000][SIZE=5][B]Title[/B][/SIZE][/COLOR]Title complement[/COLOR]

[COLOR=#808000][SIZE=2][B][SIZE=1]Text[/SIZE] Text 2[/B][/SIZE][/COLOR]
[I][SIZE=2][URL='http://www.google.fr']View: http://www.google.fr[/URL][/SIZE][/I]
[/CENTER]
 
 
this should not be centered but it is...


BUG simplified:[ FIXED => REGEX TOO GREEDY]
[CENTER][COLOR=#808000][SIZE=5]Title[/SIZE][/COLOR][B] Title complement[/B]

[/CENTER]
  
this should not be centered but it is...


Works
[CENTER][COLOR=#808000][SIZE=5]Title[/SIZE][/COLOR]

[/CENTER]
  
this should not be centered but it is...


**********************************
	NOTE
**********************************

#Controller:  XenForo_ControllerPublic_Thread
Function: actionAddReply()
Callback: XenForo_Helper_String::autoLinkBbCode

#Helper & Function: XenForo_Helper_String::autoLinkBbCode
Callback:
		$parser = new XenForo_BbCode_Parser(XenForo_BbCode_Formatter_Base::create('XenForo_BbCode_Formatter_BbCode_AutoLink', false));
		return $parser->render($string);


***********************************/