<?php include __DIR__ . "/header.php" ?>
<?php $site_title = get_bloginfo('name'); ?>

<body>
	<table class="email-wrapper" width="100%" cellpadding="0" cellspacing="0">
		<tr>
			<td align="center">
				<table class="email-content" width="100%" cellpadding="0" cellspacing="0">
					<tr>
						<td class="email-masthead" style="font-size: 28px; font-weight: 500; color: white; background: #222E50; font-family: serif; padding: 15px 0;">New contact us form Submission </td>
					</tr>
					<!-- Email Body -->
					<tr>
						<td class="email-body" width="100%" cellpadding="0" cellspacing="0">
							<table class="email-body_inner" align="center" width="570" cellpadding="0" cellspacing="0">
								<!-- Body content -->
								<tr>
									<td class="content-cell">
										<h1><?php echo __('New contact us Form Submission', 'plates'); ?></h1>

										<p>User Name: <?php echo esc_html($name); ?></p>
										<p>User Email: <?php echo esc_html($email); ?></p>
										<p>User Message: <?php echo esc_html($message); ?></p>
									</td>
								</tr>
							</table>
						</td>
					</tr>

					<?php include __DIR__ . "/footer.php" ?>