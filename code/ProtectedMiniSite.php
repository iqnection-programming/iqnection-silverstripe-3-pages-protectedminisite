<?php

	class ProtectedMiniSiteUser extends DataObject
	{
		private static $db = array(
			'Username' => 'Varchar(255)',
			'Password' => 'Varchar(255)',
			'UniqueHash' => 'Varchar(32)',
			'AccessPageIDs' => 'Text'			
		);
		
		private static $has_one = array(
			'ProtectedMiniSite' => 'ProtectedMiniSite'
		);
		
		private static $summary_fields = array(
			'Username' => 'Username'
		);
					
		function getCMSFields()
		{
			$fields = parent::getCMSFields();
			$fields->removeByName('UniqueHash');
			
			// create a list of all child pages 
			if ($this->ProtectedMiniSiteID)
			{
				$pagesArray = array();
				$this->getPagesArray($this->ProtectedMiniSiteID,$pagesArray,0);			
				$fields->addFieldToTab('Root.Main', $pagesField = new CheckboxSetField('AccessPageIDs','Allowed Pages',$pagesArray) );
				$pagesField->addExtraClass('vertical');
			}
			else
			{
				$fields->removeByName('AccessPageIDs');
				$fields->addFieldToTab('Root.Main', new LiteralField('note','<div class="field"><label class="left">Access Pages</label><div class="middleColumn">You must save once before you can select allowed pages</div></div>') );
			}
			return $fields;
		}
		
		function getPagesArray($ParentID,&$pagesArray,$level)
		{
			if ($pages = DataObject::get('SiteTree',"ParentID = ".$ParentID))
			{
				foreach($pages as $page)
				{
					$pagesArray[$page->ID] = str_repeat("~&nbsp;",$level).$page->Title;
					$this->getPagesArray($page->ID,$pagesArray,$level+1);
				}
			}			
		}
		
		function onBeforeWrite()
		{
			parent::onBeforeWrite();
			if (!$this->UniqueHash)
			{
				$this->UniqueHash = md5(strtotime('now').'|'.rand());
			}
		}
		
		public function canCreate($member = null) { return true; }
		public function canDelete($member = null) { return true; }
		public function canEdit($member = null)   { return true; }
		public function canView($member = null)   { return true; }
		
		function Login()
		{
			Cookie::set('_pmspu',$this->UniqueHash,0);
		}
		
		function Logout()
		{
			Cookie::set('_pmspu',false);
			Cookie::force_expiry('_pmspu');
		}
		
		function CanAccessPage($PageID)
		{
			$accessArray = explode(',',$this->AccessPageIDs);
			return in_array($PageID,$accessArray);
		}
		
		protected static $currentUser;
		static function CurrentSecureUser()
		{
			if ( (!self::$currentUser) && ($UniqueHash = Cookie::get('_pmspu')) )
			{
				self::$currentUser = DataObject::get_one('ProtectedMiniSiteUser',"UniqueHash = '".$UniqueHash."'");
			}
			return self::$currentUser;
		}
	}
	
	class ProtectedMiniSite extends Page
	{
		private static $allowed_children = array(
			'ProtectedMiniSiteChildPage'
		);
		
		private static $has_many = array(
			'ProtectedMiniSiteUsers' => 'ProtectedMiniSiteUser'
		);
		
		function getCMSFields()
		{
			$fields = parent::getCMSFields();
			
			$fields->addFieldToTab('Root.Content.Users', new GridField(
				'ProtectedMiniSiteUsers',
				'Secure Users',
				$this->ProtectedMiniSiteUsers(),
				GridFieldConfig_RecordEditor::create()->addComponent(
					'GridFieldButtonRow'
				)
			));
			
			return $fields;
		}
		
		function FindTopParent()
		{
			return $this;
		}
	}
	
	class ProtectedMiniSite_Controller extends Page_Controller
	{
		static $allowed_actions = array(
			'login',
			'SecureLoginForm',
			'logout'
		);
		
		function init()
		{
			parent::init();
		}
		
		function PageCSS()
		{
			return array_merge(
				parent::PageCSS(),
				array(
					ViewableData::themeDir().'/css/form.css',
					'iq-protectedminisite/css/pages/ProtectedMiniSite.css'
				)
			);
		}
		
		function index()
		{
			// check if the user has access to this page
			if (!$user = ProtectedMiniSiteUser::CurrentSecureUser())
			{
				return $this->redirect($this->Link('login'));
			}
			return $this;
		}
		
		function SecureLoginForm()
		{
			if ($message = Session::get('FormError')) Session::set('FormError',false);
			$form = new Form(
				$this,
				'SecureLoginForm',
				new FieldList(
					new LiteralField('message',($message) ? '<p class="form-error">'.$message.'</p>' : null),
					new TextField('Username','Username'),
					new PasswordField('Password','Password')
				),
				new FieldList(
					new FormAction('doProtectedPagesLogin','Login')
				),
				new RequiredFields(
					array('Username','Password')
				)
			);
			$this->extend('updateSecureLoginForm',$form);


			return $form;
		}
		
		function doProtectedPagesLogin($data,$form)
		{
			if (empty($data['Username']) || empty($data['Password']))
			{
				return $this->redirect($this->Link('login'));
			}
			
			if ($user = DataObject::get_one('ProtectedMiniSiteUser',"ProtectedMiniSitePageID = ".$this->ID." AND Username = '".Convert::raw2sql($data['Username'])."' AND Password = '".Convert::raw2sql($data['Password'])."'"))
			{
				$user->login();
				return $this->redirect($this->Link());
			}
			Session::set('FormError','Invalid Username/Password');
			return $this->redirect($this->Link('login'));
		}
		
		function logout()
		{
			if ($user = ProtectedMiniSiteUser::CurrentSecureUser()) $user->Logout();
			return $this->redirect($this->Link());
		}
	}
	
?>