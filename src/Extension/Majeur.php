<?php
/**
 * @component     Plugin User Majeur
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @copyright (c) 2025 ConseilGouz. All Rights Reserved.
 * @author ConseilGouz
 */
namespace ConseilGouz\Plugin\User\Majeur\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Database\DatabaseInterface;
use Joomla\Utilities\ArrayHelper;
use Joomla\Event\SubscriberInterface;

class Majeur extends CMSPlugin implements SubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'onUserBeforeSave'   => 'onUserBeforeSave',
            'onUserAfterSave'   => 'onUserAfterSave'
        ];
    }

	public function onUserBeforeSave($event) // ($user, $isnew, $data)
	{
        $data = $event[2];
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
	public function onUserAfterSave($event) // ($data, $isNew, $result, $error)
	{
        $data = $event[0];
        $isNew = $event[1];
        $result = $event[2];
        
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
