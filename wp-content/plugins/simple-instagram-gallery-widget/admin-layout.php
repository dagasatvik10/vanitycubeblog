<?php

$Layout = '
<div class="wrap">
 <h1>Instagram Gallery Widget</h1>
  <p><i>First, register a new Instagram Client.<br />
  Response URL should be: '.plugins_url('callback.php',__FILE__).'</i></p>
  <form action="" method="POST" id="form">

   <table class="form-table">
	<tr>
	 <th><label>Client ID:</label></th>
	 <td><input type="text" name="instagram_client_id" class="regular-text" value="' . get_option("instagram_client_id") .'" /></td>
	</tr>
	<tr>
	 <th><label>Client Secret:</label></th>
	 <td><input type="text" name="instagram_client_secret" class="regular-text" value="' . get_option("instagram_client_secret") .'" /></td>
	</tr>
   </table>
	 <p />
	 <input type="submit" name="sub" value="Save Settings">
  </form>	
</div>
';

?>