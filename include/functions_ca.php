<?PHP
function switch_ca_form() {
session_unset();
$config=update_config();
$dh = opendir($config['certstore_path']) or die('Fatal: Unable to opendir Certificate Store.');
?>
<b>Switch to a different CA<br \></b>
If you wish to create a new Sub-CA please select create CSR and select device type as Sub_CA.

<form action="index.php" method="post">
<input type="hidden" name="menuoption" value="switchca"/>
<table style="width: 350px;">
<tr><td>Certificate Authority:<td><select name="ca_name" rows="6">
<option value="">--- Select a CA
<option value="zzCREATEZZnewZZ">Create New Root CA</option>
<?PHP
while (($file = readdir($dh)) !== false) {
//	if (substr($file, -4) == ".csr") {
	if ( is_dir($config['certstore_path'].$file) && ($file != '.') && ($file != '..') ) {
		print "<option>$file";
	}
}
?>
</select>
<tr><td><td><input type="submit" value="Submit CA"/>
</table>
</form>
</p>
<?PHP
}

function delete_ca_form(&$my_errors=array('errors' => FALSE)) {
session_unset();
?>
<h1>PHP-CA Delete CA</h1>
<?PHP
$config=update_config();
$dh = opendir($config['certstore_path']) or die('Fatal: Unable to opendir Certificate Store.');
if ($my_errors['errors']) {
  if (!$my_errors['valid_text'])
    print "<b><font color='red'> Error. Please enter the correct confirmation text. DELETEME</font><BR></b>\n\n";
  if (!$my_errors['valid_ca_name'])
    print "<b><font color='red'> Error. Please select a valid certificate authority</font><BR></b>\n\n";
  }
	
?>
<p>
<b>Delete a certificate authority</b><br/>
This is NON REVERSIBLE!!
You will not be prompted any further once you enter the details and click submit!!
<form action="index.php" method="post">
<input type="hidden" name="menuoption" value="delete_ca"/>
<table style="width: 350px;">
<tr><td>Please type DELETEME<BR>all one word.<td><input type="text" name="confirm_text" value="XXXX">
<tr><td>Certificate Authority:<td><select name="ca_name" rows="6">
<option value="zzzDELETECAzzz">--- Select a CA
<?php
while (($file = readdir($dh)) !== false) {
//	if (substr($file, -4) == ".csr") {
	if ( is_dir($config['certstore_path'].$file) && ($file != '.') && ($file != '..') ) {
		print "<option>$file";
	}
}
?>
</select></tr>
<tr><td><td><input type="submit" value="Submit CA to delete"/>
</table>
</form>
</p>
<?PHP
}

function rrmdir($dir) {
    if (is_dir($dir)) {
      $objects = scandir($dir);
      foreach ($objects as $object) {
        if ($object != "." && $object != "..") {
          if (filetype($dir."/".$object) == "dir") rrmdir($dir."/".$object); else unlink($dir."/".$object);
        }
      }
      reset($objects);
      rmdir($dir);
    }
  }

function delete_ca($my_certstore,$my_ca_name){
//$this_dir = $my_certstore.htmlspecialchars($my_ca_name);
$this_dir = $my_certstore.$my_ca_name;
if (is_dir($this_dir)) {
  rrmdir($this_dir);
  print "<h2> Certificate Authority $my_ca_name Deleted!!</h2>";
  }
else
  print "Unable to delete folder. Please check file permissions.";  
}

function create_ca_form() {
$_SESSION['my_ca']='create_ca';
?>
<p>
<b>Create a new Root Certificate Authority</b><br/>
<form action="index.php" method="post">
<input type="hidden" name="create_ca" value="create_ca"/>
<input type="hidden" name="menuoption" value="create_ca"/>
<input type="hidden" name="device_type" value="ca_cert"/>

<table  style="width: 400px;">
<tr><th width=100>Common Name (eg root-ca.golf.local)</th><td><input type="text" name="cert_dn[commonName]" value="ABC Widgets Certificate Authority" size="40"></td></tr>
<tr><th>Contact Email Address</th><td><input type="text" name="cert_dn[emailAddress]" value="cert@abcwidgets.com" size="30"></td></tr>
<tr><th>Organizational Unit Name</th><td><input type="text" name="cert_dn[organizationalUnitName]" value="Certification" size="30"></td></tr>
<tr><th>Organization Name</th><td><input type="text" name="cert_dn[organizationName]" value="ABC Widgets" size="25"></td></tr>
<tr><th>City</th><td><input type="text" name="cert_dn[localityName]" value="Beverly Hills" size="25"></td></tr>
<tr><th>State</th><td><input type="text" name="cert_dn[stateOrProvinceName]" value="California" size="25"></td></tr>
<tr><th>Country</th><td><input type="text" name="cert_dn[countryName]" value="US" size="2"></td></tr>
<tr><th>Key Size</th><td><input type="radio" name="keySize" value="1024" /> 1024bits <input type="radio" name="keySize" value="2048" /> 2048bits<input type="radio" name="keySize" value="4096" checked /> 4096bits</td></tr>
<tr><th>Number of Days</th><td><input type="text" name="cert_dn[days]" size="4" value="7300" /></td></tr>
<tr><th>Certificate Passphrase</th><td><input type="password" name="passphrase"/></td></tr>
<tr><td><td><input type="submit" value="Create Root CA"/>
</table>
</form> 
</p>

<?PHP
}


function create_ca($my_certstore_path, $my_keysize, $my_device_type,$my_cert_dn,$my_passphrase) {

//if (!is_dir($my_certstore_path.$my_cert_dn['commonName']))
  create_cert_store($my_certstore_path, $my_cert_dn['commonName']);
//else
//  die('Fatal: CA Store already exists for '. $my_cert_dn['commonName']);

$my_days=$my_cert_dn['days'];
unset($my_cert_dn['days']);
$my_csrfile=create_csr($my_cert_dn, $my_keysize, $my_passphrase, $my_device_type, NULL);
sign_csr($my_passphrase,$my_csrfile,$my_days,$my_device_type);
//to do, check sign_csr code for device type
}


function download_crl_form(){
$config=$_SESSION['config'];
$this_ca=$_SESSION['my_ca'];

//Sign an existing CSR code form. Uses some PHP code first to ensure there are some valid CSRs available.
$valid_files=0;
$dh = opendir($config['crl_path']) or die('Unable to open crl path');
while (($file = readdir($dh)) !== false) {
	if ( ($file !== ".htaccess") && is_file($config['crl_path'].$file) )  {
	  if (is_file($config['crl_path'].$file) ) {
	    $valid_files++;
	  }
	}
}
closedir($dh);

if ($valid_files) {
?>
<p>
<b>Download a CRL</b><br/>
<form action="index.php" method="post">
<input type="hidden" name="menuoption" value="download_crl">
<table  style="width: 400px;">

<tr><th>Rename Extension</th><td><input type="radio" name="rename_ext" value="FALSE" checked />Do not Rename<br><input type="radio" name="rename_ext" value="crl" /> Rename to crl</td></tr>
<tr><th>Rename Filename to <BR><?PHP print $this_ca;?></th><td><input type="checkbox" name="rename_filename"/></td></tr>

<tr><td width=100>Name:<td><select name="crl_name" rows="6">
<option value="">--- Select a CRL
<?php
$dh = opendir($config['crl_path']) or die('Unable to open ' . $config['crl_path']);
while (($file = readdir($dh)) !== false) {
	if ( ($file !== ".htaccess") && is_file($config['crl_path'].$file) )  {
		$name = substr($file, 0,strrpos($file,'.'));
		$ext = substr($file, strrpos($file,'.'));
		print "<option value=\"$name$ext\">$name$ext</option>\n";
	}
}
?>
</select></td></tr>
<tr><td><td><input type="submit" value="Download CRL File">
</table>
</form>
</p>
<?PHP
}
else 
  print "<b> No Valid CRLs are available to download.</b>\n";
}

function download_crl($this_crl,$crl_ext,$crl_filename) {
$this_ca=$_SESSION['my_ca'];
$config=$_SESSION['config'];
if (!isset($crl_ext)) 
  $crl_ext='FALSE';

$filename = substr($this_crl, 0,strrpos($this_crl,'.'));
$ext=substr($this_crl, strrpos($this_crl,'.'));
$download_crlfile = $config['crl_path']. $filename.$ext;
$application_type='application/octet-stream';

if ($crl_ext != 'FALSE') 
  $ext='.'.$crl_ext;

if ($crl_filename != 'off') 
  $filename=$this_ca;

if (file_exists($download_crlfile)) {
  $myCRL = join("", file($download_crlfile));
  download_header_code($filename.$ext,$myCRL,$application_type);
  }
else {
  printHeader("Certificate Retrieval");
  print "<h1> $filename - X509 CRL not found</h1>\n";
  printFooter();
  }
}


function renew_ca_form() {
  ?>
  <p>
  <b>Renew Root Certificate Authority</b><br/>
  <?PHP
    $my_cert = parse_cert(THIS_CA_CERT_NAME);
    if(! is_cert_selfsigned($my_cert))  
    {
      print ("Only root certificates can be renewed by this function");
      return;
    }

  ?>
  <form action="index.php" method="post">
    <input type="hidden" name="menuoption" value="renew_ca"/>
  <input type="hidden" name="device_type" value="ca_cert"/>
  
  <table  style="width: 400px;">
  <tr><th width=100>Common Name (eg root-ca.golf.local)</th><td><input type="text" name="cert_dn[commonName]" value="<?= $my_cert['subject']['CN'] ?>" size="40"></td></tr>
  <tr><th>Contact Email Address</th><td><input type="text" name="cert_dn[emailAddress]" value="<?= $my_cert['subject']['emailAddress'] ?>" size="30"></td></tr>
  <tr><th>Organizational Unit Name</th><td><input type="text" name="cert_dn[organizationalUnitName]" value="<?= $my_cert['subject']['OU'] ?>" size="30"></td></tr>
  <tr><th>Organization Name</th><td><input type="text" name="cert_dn[organizationName]" value="<?= $my_cert['subject']['O'] ?>" size="25"></td></tr>
  <tr><th>City</th><td><input type="text" name="cert_dn[localityName]" value="<?= $my_cert['subject']['L'] ?>" size="25"></td></tr>
  <tr><th>State</th><td><input type="text" name="cert_dn[stateOrProvinceName]" value="<?= $my_cert['subject']['ST'] ?>" size="25"></td></tr>
  <tr><th>Country</th><td><input type="text" name="cert_dn[countryName]" value="<?= $my_cert['subject']['C'] ?>" size="2"></td></tr>
  <tr><th>Number of Days</th><td><input type="text" name="cert_dn[days]" size="4" value="7300" /></td></tr>
  <tr><th>Certificate Passphrase</th><td><input type="password" name="passphrase"/></td></tr>
  <tr><th>Keep old private key</th><td><input type="checkbox" name="keep_key" checked/></td></tr>
  <tr><td><td><input type="submit" value="Renew Root CA"/>
  </table>
  </form> 
  </p>
  <?PHP
}

function renew_ca($my_passphrase, $my_cert_dn, $my_keysize){
  //openssl req -new -key root.key -out newcsr.csr
  //openssl x509 -req -days 3650 -in newcsr.csr -signkey root.key -out newroot.pem
  //rm newcsr.csr
  $my_cert = parse_cert(THIS_CA_CERT_NAME);
  if(! is_cert_selfsigned($my_cert))  
  {
    print ("Only root certificates can be renewed by this function");
    return;
  }
  $config=$_SESSION['config'];
  $my_days=$my_cert_dn['days'];
  unset($my_cert_dn['days']);
  $my_csrfile=create_csr($my_cert_dn, $my_keysize, $my_passphrase, "ca_cert", $config['cakey']);
  sign_csr($my_passphrase, $my_csrfile, $my_days, "ca_cert");
}

function edit_ca_config_form() {
  $config=$_SESSION['config'];
    ?>
  <p>
  <b>Edit CA config</b><br/>
  <form action="index.php" method="post">
    <input type="hidden" name="menuoption" value="edit_ca_config_save"/>
  
  <table  style="width: 400px;">
  <tr><td><textarea name="ca_config_text" cols=100 rows=20><?= file_get_contents($config['config'])?> </textarea></td></tr>
  <tr><td><input type="submit" value="Save CA config"/></td>
  </table>
  </form> 
  <form action="index.php" method="post">
    <input type="hidden" name="menuoption" value="edit_ca_config_reset"/>
    <input type="submit" style="margin-top: 20px;" value="Restore original CA config"/></td>
  </form> 
  </p>
  <?PHP
}

function edit_ca_config_save($new_config_content) {
  $config=$_SESSION['config'];

  $new_fp = fopen($config['config'],"w") or die('Unable to open OPENSSL.CONF new file');
  
  fwrite($new_fp, $new_config_content);
  fclose($new_fp);  

  edit_ca_config_form();
}

function edit_ca_config_reset() {
  $config=$_SESSION['config'];
  install_openssl_config($config);
  edit_ca_config_form();
}


?>
