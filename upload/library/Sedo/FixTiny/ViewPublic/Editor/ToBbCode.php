<?php

class Sedo_FixTiny_ViewPublic_Editor_ToBbCode extends XFCP_Sedo_FixTiny_ViewPublic_Editor_ToBbCode
{
	private $guiltyTags;
	private $RegexFixTagPollution_A;
	private $RegexFixTagPollution_B;	

	private $beforeTags;
	private $afterTags;
	
	public function renderJson()
	{
		$parent = parent::renderJson();
      		$options = XenForo_Application::get('options');

		if(!empty($options->tinymce_fix_housewife_tabs))
		{
			$parent['bbCode'] = $this->fixTinyTabs($parent['bbCode']);
		}

		if(!empty($options->tinymce_fix_tagspolution) && !empty($options->tinymce_fix_tagspolution_tags))
		{  
			$this->InitFixTagPollution();
			$parent['bbCode'] = $this->FixTagPollution($parent['bbCode']);
			//Fix duplicated consecutive tags: [b][b] => [b]
			$parent['bbCode'] = preg_replace('#(\[(?:/)?[^\]]+\])(?:\1)+#ui', '$1', $parent['bbCode']);
		}

		return $parent;
	}
	
	private function fixTinyTabs($string)
	{
		//Tabs fix: retrieve simulated 'space tabs' from Rte Editor and make them real tabs inside the standard editor
		$string = preg_replace('# {4}#', "\t", $string);
		
		return $string;
	}


	private function InitFixTagPollution()
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
      		
      		$this->guiltyTags = implode('|', $bakeGuiltyTags);

     		//Bake Regex Pattern
		$this->RegexFixTagPollution_A = '#\[(' . $this->guiltyTags  . ')(=.+?)?\].+?\[/\1\](?:[\s]+\[\1(?:\2)?\].+?\[/\1\])+#iu';

		/*** JUST FOR REFERENCE
		$this->RegexFixTagPollution_B = '/(?x)					#active regex comments
				(?P<beforeTags>(?:\[(?:' . $this->guiltyTags . ')\])+) 	#capture guiltyTags before content
				(?P<content>						#capture content starts
				(?:\s+?)?						#sometimes some linebreaks can be found
				\[(?!(?:\/)?(?:' . $this->guiltyTags . '))(.+?)\]	#get all bbcodes which tags are not the guilty ones - capture opening tag
				((?:(?2)|.)+?)						#regex conditional with recursive mask (?2) to get all code until...
				\[\/\3\]						#closing tag
				(?:\s+?)?						#sometimes some linebreaks can be found
				)							#close content capture			
				(?P<afterTags>(?:\[\/(?:' . $this->guiltyTags . ')\])+)	#capture gulityTags after content (should be the same than those before content but with reverser order)
				/siu';							//Options: Dot matches new line + don't make it case sensitive + unicode
		***/
	}
	
	private function FixTagPollution($string)
	{
  		/* Just keep for reference (regex not enough good)
  			$string = $this->FixTagPollution_B($string);
		*/
		
  		$string = $this->FixTagPollution_A($string);

		return $string;
	}

	private function FixTagPollution_A($string)
	{
		$string = preg_replace_callback($this->RegexFixTagPollution_A, array($this, 'FixTagPollution_A_L1'), $string);
		
		return $string;
	}

    	private function FixTagPollution_A_L1($matches)
	{
		$openingTag = $matches[1];
		$closingTag = $matches[1];		

		if(isset($matches[2]))
		{
			$openingTag .= $matches[2]; //options
		}
		
		$openingTag = '[' . $openingTag . ']';
		$closingTag = '[/' . $closingTag . ']';
		
		//BBcodes level 1
		$matches[0] = str_replace($openingTag, '', $matches[0]);
		$matches[0] = str_replace($closingTag, '', $matches[0]);		
		$matches[0] = $openingTag . $matches[0] . $closingTag;

	
		//For nested bbcodes, let's start the crazy loop
		if(preg_match($this->RegexFixTagPollution_A, $matches[0]))
		{
			$matches[0] = $this->FixTagPollution_A($matches[0]);
		}

		return $matches[0];
	}

      	/**************************
	*  JUST FOR REFERENCE    	
      	**************************/

	private function FixTagPollution_B($string)
	{
		$string = preg_replace_callback($this->RegexFixTagPollution_B, array($this, 'FixTagPollution_B_L1'), $string);
		
		return $string;
	}

    	private function FixTagPollution_B_L1($matches)
	{
		$beforeTags = $matches['beforeTags'];
		$content = $matches['content'];
		$afterTags = $matches['afterTags'];

		//Check if there is a carriage return before wrapped Bb Code (opening tag) and after wrapped Bb Code (closing tag)
		$private_beforeTags = '';
		$private_afterTags = '';		

		if(preg_match('#^\s*\n#s', $content))
		{
			$private_beforeTags = $matches['beforeTags'];
		}

		if(preg_match('#\n$#s', $content))
		{
			$private_afterTags = $matches['afterTags'];
		}

		//If beforeTags or afterTags are in begin or ending line position of $content => delete
		$content = preg_replace('#^' . preg_quote($beforeTags) . '#mui', '', $content);
		$content = preg_replace('#' . preg_quote($afterTags) . '$#mui', '', $content);

		//Can't do the crazy loop because the regex B will always match the modified content => loop error

		return $beforeTags . $content  . $afterTags;
	}		
}
//Zend_Debug::dump($class);