<?php

//v0.0.1

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "RestRequest.inc.php";

class ptisp extends DomainModule {

  // INFO Displayed on the module management section
  protected $description='PTisp Hostbill module';


  /*
  * This variable will be auto-loaded by HostBill before any action will be taken by module.
  */
  protected $client_data=array();

  protected $configuration = array(
    'username'=>array(
    'value'=>'',
    'type'=>'input',
    'default'=>false
    ),
    'password'=>array(
    'value'=>'',
    'type'=>'password',
    'default'=>false
    ) ,
    'test'=>array(
    'value'=>'',
    'type'=>'check'
    ),
    'type'=>array(
    'value'=>'',
    'type'=>'select',
    'default' => array('Reseller', 'Retailer') // we need to provide array with available values for 'select' type
    ),
    'nic'=>array(
    'value'=>'',
    'type'=>'input',
    'default' => false
    )
  );
  /*
  * Translation of the configuration fields
  */
  protected $lang=array(
    'english'=>array(
    'username'=>'API Username',
    'password'=>'API Password',
    'test'=>'Test Mode',
    'type'=>'Account Type',
    'nic'=>'Reseller nic-handle',
    )
  );

  /*
  * please REMOVE ALL UNNECESARRY COMMANDS
  */
  protected $commands = array('Register', 'Renew');

  public function Register() {
    $user = $this->configuration['username']['value'];
    $pass = $this->configuration['password']['value'];
    $vars['nichandle'] = $this->configuration['nic']['value'];
    // all required parameters for successfull domain registration.
    $domain = $this->params['sld'] . '.' . $this->params ['tld'];
    $period = $this->options['numyears'];
    //$vars['contact'] = '';

    //Criar owner contact


    $params = array(
      'name' => $this->client_data['firstname'] . ' ' . $this->client_data['lastname'],
      'company' => $this->client_data['companyname'],
      'street' => $this->client_data['address1'],
      'city' => $this->client_data['city'],
      'state' => $this->client_data['state'],
      'country' => $this->client_data['country'],
      'postalcode' => $this->client_data['postcode'],
      'mail' => $this->client_data['email'],
      'phone' => $this->client_data['phonenumber'],
      'vat' => $this->client_data['vat']
    );
    $request = new RestRequest("https://api.ptisp.pt/domains/" . $domain . "/contacts/create", "POST");
    $request->setUsername($username);
    $request->setPassword($password);

    $request->execute($params);
    $result = json_decode($request->getResponseBody(), true);

    if ($result["result"] == "ok" )
      $vars['contact'] = $result["nichandle"];
    else{
      $this->addError($result['message']);
      return false;
    }


    // Enviar Registo
    $request = new RestRequest("https://api.ptisp.pt/domains/" . $domain . "/register/" . $period, "POST");
    $request->setUsername($user);
    $request->setPassword($pass);
    $request->execute($var);

    $result = json_decode($request->getResponseBody(), true);

    if($result["result"] == "ok") { // SUCCESS !!!

      // change status of the domain !
      $this->addDomain('Active');
      $this->addInfo('Domain has been registered');            
      return true;

    } else {
      $this->addError($result['message']);
      return false;
    }
  }


  public function Renew() {

    $user = $this->configuration['username']['value'];
    $pass = $this->configuration['password']['value'];
    $domain = $this->params['sld'] . '.' . $this->params ['tld'];
    $period = $this->options['numyears'];

    $request = new RestRequest("https://api.ptisp.pt/domains/" . $domain . "/renew/" . $period, "POST");
    $request->setUsername($user);
    $request->setPassword($pass);
    $request->execute();

    $result = json_decode($request->getResponseBody(), true);

    if($result["result"] == "ok") { // SUCCESS !!!
      $this->addPeriod();                        // This method is
      $this->addInfo('Domain has been renewed');
      return true;

    } else {
      $this->addError('Domain renewal failed');
      return false;
    }
  }

  public function getNameServers() {
    $user = $this->configuration['username']['value'];
    $pass = $this->configuration['password']['value'];

    $request = new RestRequest("https://api.ptisp.pt/domains/" . $domain . "/info", "GET");
    $request->setUsername($user);
    $request->setPassword($pass);
    $request->execute();

    $result = json_decode($request->getResponseBody(), true);

    if($result["result"] == "ok") { // SUCCESS !!!
      // just a simple array with the nameservers of the domain
      return array(
        $result['data']['ns'][0],
        $result['data']['ns'][1],
        $result['data']['ns'][2],
        $result['data']['ns'][3],
      );
    } else {
      $this->addError('Unable to get Name Servers');
    }
  }


  public function updateNameServers() {
    $user = $this->configuration['username']['value'];
    $pass = $this->configuration['password']['value'];

    $params = array(
      'sld' => $this->options['sld'],
      'tld' => $this->options['tld'],
      'nameserver1' => $this->params['ns1'],
      'nameserver2' => $this->params['ns2'],
      'nameserver3' => $this->params['ns3'],
      'nameserver4' => $this->params['ns4']
    );

    if (isset($this->params['ns1']) && $this->params['ns1'] != ''){
      $ns = $this->params['ns1'];
      if (isset($this->params['ns2']) && $this->params['ns2'] != ''){
        $ns .= '/' . $this->params['ns2'];
      }
      if (isset($this->params['ns3']) && $this->params['ns3'] != ''){
        $ns .= '/' . $this->params['ns3'];
      }
      if (isset($this->params['ns4']) && $this->params['ns4'] != ''){
        $ns .= '/' . $this->params['ns4'];
      }

      $request = new RestRequest("https://api.ptisp.pt/domains/" . $domain . "/update/ns/" . $ns, "POST");
      $request->setUsername($user);
      $request->setPassword($pass);
      $request->execute(array());

      $result = json_decode($request->getResponseBody(), true);

      if($result["result"] == "ok") { // SUCCESS !!!
        $this->addInfo('Name Servers Updated');
      } else {
        $this->addError('Unable to get Name Servers');
      }
    } else {
      $this->addError('Nothing to update or ns1 is blank');
    }

  }

}
?>
