<?php
if (!defined('_PS_VERSION_'))
  exit;

class BankBBL extends PaymentModule{

	public $bank_name;
	public $bank_number;
	public $bank_branch;
	public function __construct(){
		$this->name = "bankbbl";
		$this->tab = 'payments_gateways';
		$this->version = '0.1.0';
		$this->author = "JindaTheme.com";
		$this->controllers = array('payment', 'validation');
		$this->is_eu_compatible = 1;

		$this->need_instance = 0;
		$this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);

		$config = Configuration::getMultiple(array('BBL_ACCOUNT_NAME', 'BBL_ACCOUNT_NUMBER', 'BBL_ACCOUNT_BRANCH'));
		if (!empty($config['BBL_ACCOUNT_NAME']))
			$this->bank_name = $config['BBL_ACCOUNT_NAME'];
		if (!empty($config['BBL_ACCOUNT_NUMBER']))
			$this->bank_number = $config['BBL_ACCOUNT_NUMBER'];
		if (!empty($config['BBL_ACCOUNT_BRANCH']))
			$this->bank_branch = $config['BBL_ACCOUNT_BRANCH'];

		$this->bootstrap = true;
		parent::__construct();

		$this->displayName = $this->l('Bank BBL');
    $this->description = $this->l('รับการโอนเงินด้วยธนาคารกรุงเทพ');
		$this->confirmUninstall = $this->l('คุณต้องการที่จะลบโมดูลนี้จริงๆ หรือไม่?');

		if (!Configuration::get('BBL_ACCOUNT_NAME'))
      $this->warning = $this->l('คุณยังไม่ได้ตั้งค่า ชื่อบัญชี');
	}

	public function install(){
		if (!parent::install() || !$this->registerHook('payment') || !$this->registerHook('paymentReturn') || !$this->registerHook('header'))
			return false;
		Configuration::updateValue('PS_OS_BBLBANKWIRE', 14);
		return true;
	}

	public function uninstall(){
		if (!parent::uninstall() || !Configuration::deleteByName('BBL_ACCOUNT_NAME') || !Configuration::deleteByName('BBL_ACCOUNT_NUMBER') || !Configuration::deleteByName('BBL_ACCOUNT_BRANCH') )
			return false;
		return true;
	}

	public function getContent() {
		$output = null;

		if (Tools::isSubmit('submit'.$this->name)) {
			$bbl_name = strval(Tools::getValue('BBL_ACCOUNT_NAME'));
			$bbl_number = strval(Tools::getValue('BBL_ACCOUNT_NUMBER'));
			$bbl_branch = strval(Tools::getValue('BBL_ACCOUNT_BRANCH'));
			if ( !$bbl_name || empty($bbl_name) || !Validate::isGenericName($bbl_name) )
				$output .= $this->displayError($this->l('กรุณากรอก ชื่อบัญชี ของคุณ'));
			elseif ( !$bbl_number || empty($bbl_number) || !Validate::isGenericName($bbl_number) )
				$output .= $this->displayError($this->l('กรุณากรอก เลขที่บัญชี ของคุณ'));
			else{
				Configuration::updateValue('BBL_ACCOUNT_NAME', $bbl_name);
				Configuration::updateValue('BBL_ACCOUNT_NUMBER', $bbl_number);
				Configuration::updateValue('BBL_ACCOUNT_BRANCH', $bbl_branch);
				$output .= $this->displayConfirmation($this->l('Settings updated'));
			}
		}

		return $output.$this->displayForm();
	}

	public function displayForm() {
		$default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

		// init Fields form Array
		$fields_form[0]['form'] = array (
			'legend' => array(
				'title' => $this->l('Settings'),
			),
			'input' => array(
				array(
					'type' => 'text',
					'label' => $this->l('Account Name'),
					'name' => 'BBL_ACCOUNT_NAME',
					'required' => true
				),
				array(
					'type' => 'text',
					'label' => $this->l('Account Number'),
					'name' => 'BBL_ACCOUNT_NUMBER',
					'required' => true
				),
				array(
					'type' => 'text',
					'label' => $this->l('Branch'),
					'name' => 'BBL_ACCOUNT_BRANCH',
					'required' => false
				),
			),
			'submit' => array(
				'title' => $this->l('Save'),
				'class' => 'btn btn-success pull-right'
			)
		);

		$helper = new HelperForm();

		$helper->module = $this;
		$helper->name_controller = $this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

		// Language
    $helper->default_form_language = $default_lang;
    $helper->allow_employee_form_lang = $default_lang;

		// Title and toolbar
    $helper->title = $this->displayName;
    $helper->show_toolbar = true;        // false -> remove toolbar
    $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top
    $helper->submit_action = 'submit'.$this->name;

		$helper->toolbar_btn = array(
      'save' =>
      array(
	      'desc' => $this->l('Save'),
	      'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
	      '&token='.Tools::getAdminTokenLite('AdminModules'),
      ),
      'back' => array(
        'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
        'desc' => $this->l('Back to list')
      )
    );

		$helper->fields_value['BBL_ACCOUNT_NAME'] = Configuration::get('BBL_ACCOUNT_NAME');
		$helper->fields_value['BBL_ACCOUNT_NUMBER'] = Configuration::get('BBL_ACCOUNT_NUMBER');
		$helper->fields_value['BBL_ACCOUNT_BRANCH'] = Configuration::get('BBL_ACCOUNT_BRANCH');

		return $helper->generateForm($fields_form);

	}

	public function hookPayment($params){
		return $this->display(__FILE__, 'payment.tpl');
	}

	public function hookPaymentReturn($params){
		if (!$this->active)
			return;

		$state = $params['objOrder']->getCurrentState();
		if (in_array($state, array(Configuration::get('PS_OS_BBLBANKWIRE'), Configuration::get('PS_OS_OUTOFSTOCK'), Configuration::get('PS_OS_OUTOFSTOCK_UNPAID'))))
		{
			$this->smarty->assign(array(
				'total_to_pay' => Tools::displayPrice($params['total_to_pay'], $params['currencyObj'], false),
				'bankbblBranch' => Tools::nl2br($this->bank_branch),
				'bankbblNumber' => Tools::nl2br($this->bank_number),
				'bankbblName' => $this->bank_name,
				'status' => 'ok',
				'id_order' => $params['objOrder']->id
			));
			if (isset($params['objOrder']->reference) && !empty($params['objOrder']->reference))
				$this->smarty->assign('reference', $params['objOrder']->reference);
		}
		else
			$this->smarty->assign('status', 'failed');
		return $this->display(__FILE__, 'payment_return.tpl');
	}

	public function hookDisplayHeader(){
		$this->context->controller->addCSS($this->_path.'css/bankbbl.css', 'all');
	}

}
