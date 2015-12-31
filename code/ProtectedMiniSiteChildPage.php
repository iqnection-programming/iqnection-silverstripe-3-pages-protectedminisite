<?php

    class ProtectedMiniSiteChildPage extends Page
    {
        
        private static $db = array(
        );
        
        private static $has_one = array(
        );
        
        private static $allowed_children = array(
        );
                
        public function getCMSFields()
        {
            $fields = parent::getCMSFields();
            $fields->addFieldToTab("Root.Columns", new HTMLEditorField("LeftColumn", "Left Column Content"));
            $fields->addFieldToTab("Root.Columns", new HTMLEditorField("CenterColumn", "Center Column Content"));
            $fields->addFieldToTab("Root.Columns", new HTMLEditorField("RightColumn", "Right Column Content"));
            $fields->addFieldToTab("Root.Sidebar", new HTMLEditorField("SidebarContent", "Sidebar Content"));
            return $fields;
        }
        
        public function FindTopParent()
        {
            return $this->Parent()->FindTopParent();
        }
        
        public function CanAccess()
        {
            return (($user = ProtectedMiniSiteUser::CurrentSecureUser()) && ($user->CanAccessPage($this->ID)));
        }
    }
    
    class ProtectedMiniSiteChildPage_Controller extends Page_Controller
    {
        public function init()
        {
            parent::init();
            
            // check if the user has access to this page
            if (!$user = ProtectedMiniSiteUser::CurrentSecureUser()) {
                return $this->redirect($this->FindTopParent()->Link('login'));
            }
        }

        public function PageCSS()
        {
            return array_merge(
                parent::PageCSS(),
                array(
                    'iq-basepages/css/pages/Page.css',
                    ViewableData::themeDir().'/css/pages/Page.css',
                    'iq-protectedminisite/css/pages/ProtectedMiniSite.css'
                )
            );
        }
        
        public function index()
        {
            if (!$this->CanAccess()) {
                return $this->customise(array('Content' => 'Sorry, you do not have permission to view this page'));
            }
            return $this;
        }
    }
