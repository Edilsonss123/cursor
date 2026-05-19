<?php
/*
	File: xajaxEventPlugin.inc.php
	Contains the xajaxEventPlugin class
	@package xajax
	@license http://www.xajaxproject.org/bsd_license.txt BSD License
*/

if (!defined('XAJAX_EVENT'))         define('XAJAX_EVENT',         'xajax event');
if (!defined('XAJAX_EVENT_HANDLER')) define('XAJAX_EVENT_HANDLER', 'xajax event handler');

//SkipAIO
require dirname(__FILE__) . '/support/xajaxEvent.inc.php';
//EndSkipAIO

class xajaxEventPlugin extends xajaxRequestPlugin
{
	var $aEvents;
	var $sXajaxPrefix;
	var $sEventPrefix;
	var $sDefer;
	var $bDeferScriptGeneration;
	var $sRequestedEvent;

	// PHP8: usar __construct em vez de construtor PHP4
	function __construct()
	{
		$this->aEvents              = array();
		$this->sXajaxPrefix         = 'xajax_';
		$this->sEventPrefix         = 'event_';
		$this->sDefer               = '';
		$this->bDeferScriptGeneration = false;
		$this->sRequestedEvent      = NULL;

		if (isset($_GET['xjxevt']))  $this->sRequestedEvent = $_GET['xjxevt'];
		if (isset($_POST['xjxevt'])) $this->sRequestedEvent = $_POST['xjxevt'];
	}

	function configure($sName, $mValue)
	{
		if ('wrapperPrefix' == $sName) {
			$this->sXajaxPrefix = $mValue;
		} else if ('eventPrefix' == $sName) {
			$this->sEventPrefix = $mValue;
		} else if ('scriptDefferal' == $sName) {
			if (true === $mValue) $this->sDefer = 'defer ';
			else $this->sDefer = '';
		} else if ('deferScriptGeneration' == $sName) {
			if (true === $mValue || false === $mValue)
				$this->bDeferScriptGeneration = $mValue;
			else if ('deferred' === $mValue)
				$this->bDeferScriptGeneration = true;
		}
	}

	function register($aArgs)
	{
		if (1 < count($aArgs)) {
			$sType = $aArgs[0];

			if (XAJAX_EVENT == $sType) {
				$sEvent = $aArgs[1];
				if (false === isset($this->aEvents[$sEvent])) {
					$xe = new xajaxEvent($sEvent);
					if (2 < count($aArgs))
						if (is_array($aArgs[2]))
							foreach ($aArgs[2] as $sKey => $sValue)
								$xe->configure($sKey, $sValue);
					$this->aEvents[$sEvent] =& $xe;
					return $xe->generateRequest($this->sXajaxPrefix, $this->sEventPrefix);
				}
			}

			if (XAJAX_EVENT_HANDLER == $sType) {
				$sEvent = $aArgs[1];
				if (isset($this->aEvents[$sEvent])) {
					if (isset($aArgs[2])) {
						$xuf =& $aArgs[2];
						if (false === ($xuf instanceof xajaxUserFunction))
							$xuf = new xajaxUserFunction($xuf);
						$objEvent =& $this->aEvents[$sEvent];
						$objEvent->addHandler($xuf);
						return true;
					}
				}
			}
		}
		return false;
	}

	function generateHash()
	{
		$sHash = '';
		// PHP8: null coalescing para evitar TypeError em array_keys(null)
		foreach (array_keys($this->aEvents ?? array()) as $sKey)
			$sHash .= $this->aEvents[$sKey]->getName();
		return md5($sHash);
	}

	function generateClientScript()
	{
		foreach (array_keys($this->aEvents ?? array()) as $sKey)
			$this->aEvents[$sKey]->generateClientScript($this->sXajaxPrefix, $this->sEventPrefix);
	}

	function canProcessRequest()
	{
		if (NULL == $this->sRequestedEvent) return false;
		return true;
	}

	function processRequest()
	{
		if (NULL == $this->sRequestedEvent) return false;

		$objArgumentManager =& xajaxArgumentManager::getInstance();
		$aArgs = $objArgumentManager->process();

		foreach (array_keys($this->aEvents ?? array()) as $sKey) {
			$objEvent =& $this->aEvents[$sKey];
			if ($objEvent->getName() == $this->sRequestedEvent) {
				$objEvent->fire($aArgs);
				return true;
			}
		}
		return 'Invalid event request received; no event was registered with this name.';
	}
}

$objPluginManager =& xajaxPluginManager::getInstance();
$objPluginManager->registerPlugin(new xajaxEventPlugin(), 103);
