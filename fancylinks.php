<?php
/*------------------------------------------------------------------------
# plg_fancylinks
# ------------------------------------------------------------------------
# author &nbsp; &nbsp;Buyanov Danila - Saity74 Ltd.
# copyright Copyright (C) 2012 saity74.ru. All Rights Reserved.
# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
# Websites: http://www.saity74.ru
# Technical Support: &nbsp; http://saity74.ru/fancy-links-joomla.html
# Admin E-mail: admin@saity74.ru
-------------------------------------------------------------------------*/
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' ); 

jimport('joomla.plugin.plugin');

class plgSystemFancyLinks extends JPlugin
{
	protected $_extList;
	protected $_forceIncludeCss;
	protected $_css;
	protected $_ignoreClass;
	protected $_extentions = array();

	public function onBeforeRender()
	{
		$app =& JFactory::getApplication(); if( $app->isAdmin() ) return true;

		$this->_forceIncludeCss = $this->params->get('forcecss', 0) == 1;
		$this->_ignoreClass = $this->params->get('ignore', '');
		$this->_extList = explode("\n", $this->params->get('extlist', ''));

		if (empty($this->_extList) || !trim($this->_extList[0]))
			return true;
	
		$this->_css = 'a.fancy-link{line-height: 1em; margin: .3em 0;display:inline-block}a.fancy-link span{display: inline-block;color: #fff;text-shadow: 0 -1px 0 #666;text-align: center;text-transform: uppercase;margin-right: 1em;width: 2.7em;height: 1.4em;line-height: 1.4em;font-size: .9em;padding: 0.1em 0;border-radius: .3em;border-width: 1px;border-style: solid;font-family: Verdana}a.fancy-link.small span{font-size: 0.8em}a.fancy-link.large span{font-size: 1.2em;}';
		foreach ($this->_extList as $str)
		{
			list($ext, $color) = explode('=', $str);
			$this->_css .= $this->_getCss($ext, $color);
			$this->_extentions[] = $ext;
		}
		
		if (!$this->_forceIncludeCss)
		{
			$doc = JFactory::getDocument();
			$doc->addStyleDeclaration($this->_css);
		}
	}
	
    public function onAfterRender()
	{
		
		$app =& JFactory::getApplication(); if( $app->isAdmin() ) return true;
		$content = JResponse::getBody();
		if (JString::strpos($content, '</a>') === false) {
			return true;
		}
		
		$regex = "#<a(.*?)>(.*?)</a>#s";

		$content = preg_replace_callback($regex, array(&$this, '_replace'), $content);
		
		if ($this->_forceIncludeCss)
		{
			$content = preg_replace('#<\/head>#s','<style>'.$this->_css.'</style>'."\n".'</head>', $content, 1);
		}
		
		JResponse::setBody($content);
		return true;
	}
	
	protected function _replace(&$matches)
	{
		jimport('joomla.utilities.utility');

		$args = JUtility::parseAttributes($matches[1]);
		
		$classes = explode(' ', $args['class']);
		$ignores = explode(',', $this->_ignoreClass);
		$intersect = array_intersect($classes, $ignores);
		
		if (empty($intersect) || !$intersect[0])
		{
			
			$parse = isset($args['href']) ? pathinfo($args['href']) : null ;
			
			$args['class'] .= ' fancy-link ';
			
			if ($parse && is_array($parse) && isset($parse['extension']) && in_array($parse['extension'], $this->_extentions))
			{
				$ext = $parse['extension'];
				
				$args['class'] .= $ext;
				
				$params = '';
				foreach ($args as $key => $value)
				{
					$params .= $key.'="'.$value.'" ';
				}
				
				$text = '<a '.$params.'><span>'.$ext.'</span>'.$matches[2].'</a>';
			}
			
			else
				return $matches[0];
				
		}
		else 
			return $matches[0];
		return $text;
	}
	
	protected function _brightness($hex, $percent) {
		$hash = '';
		if (stristr($hex,'#')) {
			$hex = str_replace('#','',$hex);
			$hash = '#';
		}
		$rgb = array(hexdec(substr($hex,0,2)), hexdec(substr($hex,2,2)), hexdec(substr($hex,4,2)));
		for ($i=0; $i<3; $i++) {
			if ($percent > 0) {
				$rgb[$i] = round($rgb[$i] * $percent) + round(255 * (1-$percent));
			} else {
				$positivePercent = $percent - ($percent*2);
				$rgb[$i] = round($rgb[$i] * $positivePercent) + round(0 * (1-$positivePercent));
			}
			if ($rgb[$i] > 255) {
				$rgb[$i] = 255;
			}
		}
		$hex = '';
		for($i=0; $i < 3; $i++) {
			$hexDigit = dechex($rgb[$i]);
			if(strlen($hexDigit) == 1) {
			$hexDigit = "0" . $hexDigit;
			}
			$hex .= $hexDigit;
		}
		return $hash.$hex;
	}

	protected function _getCss($colorName, $colorHEX)
	{
		$darken = $this->_brightness($colorHEX, -0.83);
		$backgroundColor = $this->_brightness($colorHEX, -0.93);
		$borderColor = $this->_brightness($colorHEX, -0.57);
		
		$strCSS = '';
		$css = array();
		
		$css[] = 'background-color:'.$backgroundColor;
		$css[] = 'text-shadow: 0 -1px 0 '.$borderColor;
		$css[] = 'background-image: -ms-linear-gradient(top, '.$colorHEX.','.$darken.')';
		$css[] = 'background-image: -webkit-gradient(linear,0 0,0 100%,from('.$colorHEX.'),to('.$darken.'))';
		$css[] = 'background-image: -webkit-linear-gradient(top,'.$colorHEX.','.$darken.')';
		$css[] = 'background-image: -o-linear-gradient(top,'.$colorHEX.','.$darken.')';
		$css[] = 'background-image: -moz-linear-gradient(top,'.$colorHEX.','.$darken.')';
		$css[] = 'background-image: linear-gradient(top,'.$colorHEX.','.$darken.')';
		$css[] = 'background-repeat: repeat-x';
		$css[] = 'border-color: '.$colorHEX.' '.$darken.' '.$borderColor;
		$css[] = 'border-color: rgba(0,0,0,0.1) rgba(0,0,0,0.1) rgba(0,0,0,0.25)';
		$css[] = 'filter: progid:dximagetransform.microsoft.gradient(startColorstr=\''.$colorHEX.'\',endColorstr=\''.$darken.'\',GradientType=0)';
		$css[] = 'filter: progid:dximagetransform.microsoft.gradient(enabled=false)';
		
		$strCSS =  'a.fancy-link.'.$colorName.' span{'.implode($css, ';').'}'."\n";
		
		$strCSS .= 'a.fancy-link.'.$colorName.':hover span{background-color: '.$backgroundColor.'; background-image: none;}';
		
		return $strCSS;
	}
}
