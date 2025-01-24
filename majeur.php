<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  User.Majeur
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Database\DatabaseInterface;
use Joomla\Utilities\ArrayHelper;

class PlgUserMajeur extends CMSPlugin
{

	public function onUserBeforeSave($user, $isnew, $data)
	{
		// Check that the date is valid.
		if (!empty($data['profile']['dob']))
		{
			try
			{
				$date = new Date($data['profile']['dob']);
				$this->date = $date->format('Y-m-d H:i:s');
			}
			catch (\Exception $e)
			{
				// Throw an exception if date is not valid.
				throw new InvalidArgumentException(Text::_('PLG_USER_PROFILE_ERROR_INVALID_DOB'));
			}
			if (Date::getInstance('now') < $date)
			{
				// Throw an exception if dob is greather than now.
				throw new InvalidArgumentException(Text::_('PLG_USER_PROFILE_ERROR_INVALID_DOB'));
			}
		}


		return true;
	}
	/**
	 * UPDATE username with UserId
	 *
	 * @param   array    $data    entered user data
	 * @param   boolean  $isNew   true if this is a new user
	 * @param   boolean  $result  true if saving the user worked
	 * @param   string   $error   error message
	 *
	 * @return bool
	 */
	public function onUserAfterSave($data, $isNew, $result, $error)
	{

		$userId = ArrayHelper::getValue($data, 'id', 0, 'int');

		if ($isNew && $userId && $result && isset($data['profile']) && (count($data['profile'])) && ($this->params->def('major_usergroup')) && ($this->date))
		{  
		    $group = $this->params->def('major_usergroup');
			try
			{
				// Sanitize the date
				$data['profile']['dob'] = $this->date;
				$mineur = new Date('now -18 year');
				if ($data['profile']['dob'] > $mineur->format('Y-m-d H:m:s')) { 
					return true;
				}
				// on est sur majeur
				$db = Factory::getContainer()->get(DatabaseInterface::class);
				$sql = 'INSERT INTO #__user_usergroup_map VALUES ('.$userId.','.$group.')';
				$db->setQuery($sql);
				$db->execute();
			}
			catch (RuntimeException $e)
			{
				$this->_subject->setError($e->getMessage());

				return false;
			}
		}

		return true;
	}


}
