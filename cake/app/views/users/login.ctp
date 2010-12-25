
<div class="left login">
	<h3>Log In</h3>
	<?php
		echo $form->create('User', array('action' => 'login'));
		echo $form->input('username');
		echo $form->input('password');
		echo $form->end('Login');
	?>
</div>
