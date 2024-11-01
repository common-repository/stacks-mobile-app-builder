<?php include __DIR__."/header.php" ?>
<body>
<table class="email-wrapper" width="100%" cellpadding="0" cellspacing="0">
  <tr>
	<td align="center">
	  <table class="email-content" width="100%" cellpadding="0" cellspacing="0">
		<tr>
		  <td class="email-masthead" style="font-size: 28px; font-weight: 500; color: white; background: #BF1A2F; font-family: serif; padding: 15px 0;">Request Change Password</td>
		</tr>
		<!-- Email Body -->
		<tr>
		  <td class="email-body" width="100%" cellpadding="0" cellspacing="0">
			<table class="email-body_inner" align="center" width="570" cellpadding="0" cellspacing="0">
			  <!-- Body content -->
			  <tr>
				<td class="content-cell">
				  <h1>Hi <?php echo esc_html($user->display_name) ?>,</h1>
				  <p>  </p>
				  <p>We heard You would like to activate new password click the link below to activate the new password.</p>
				  <!-- Action -->
				  <table class="body-action" align="center" width="100%" cellpadding="0" cellspacing="0">
					<tr>
					    <td align="center">
						<table width="100%" border="0" cellspacing="0" cellpadding="0">
						    <tr>
							<td align="center">
							    <table border="0" cellspacing="0" cellpadding="0">
								<tr>
								    <td>
									<a href="<?php echo esc_url($link) ?>" class="button button--green" style="color:white; font-size: 15px;font-weight: bold;" target="_blank">Activate New Password</a>
								    </td>
								</tr>
							    </table>
							</td>
						    </tr>
						</table>
					    </td>
					</tr>
				  </table>
				  <p>For security, this request was received from a <?php echo esc_html($activate_new_pass_data["os"]) ?> device using <?php echo esc_html($activate_new_pass_data["browser"]) ?></p>
				  <!-- Sub copy -->
				  <table class="body-sub">
					<tr>
					  <td>
						<p class="sub">If you are having trouble with the button above, copy and paste the URL below into your web browser.</p>
						<p class="sub"><?php echo esc_url($link) ?></p>
					  </td>
					</tr>
				  </table>
				</td>
			  </tr>
			</table>
		  </td>
		</tr>

<?php include __DIR__."/footer.php" ?>