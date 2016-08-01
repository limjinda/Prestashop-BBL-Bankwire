{if $status == 'ok'}
	<div class="bbl-bank-container">
		<div class="row">
			<div class="col-sm-8 col-md-5 col-centered">

				<img src="{$modules_dir}bankbbl/success.png" class="_check-icon" alt="Completed">
					
				<h2 class="_title text-center">{l s='Your order on %s is complete.' sprintf=$shop_name mod='bankbbl'}</h2>
				<p class="text-center">{l s='Please send us a bank wire with' mod='bankbbl'}</p>

				<div class="dashed"></div>

				<div class="completed-wrapper">
					<div class="_content">
						<div class="left">
							<img src="{$modules_dir}bankbbl/bank-logo-square.jpg" alt="{l s='BBL Logo' mod='bankbbl'}" class="_logo" />
						</div>
						<div class="right">
							<p class="lead">
								<span>{l s='Amount: ' mod='bankbbl'}</span>
								{$total_to_pay}
							</p>
							<p class="lead">
								<span>{l s='Account Name: ' mod='bankbbl'}</span>
								{if $bankbblName}{$bankbblName}{else}___________{/if}
							</p>
							<p class="lead">
								<span>{l s='Account Number: ' mod='bankbbl'}</span>
								{if $bankbblNumber}{$bankbblNumber}{else}___________{/if}
							</p>
							<p class="lead">
								<span>{l s='Branch: ' mod='bankbbl'}</span>
								{if $bankbblBranch}{$bankbblBranch}{/if}
							</p>
						</div>
						<div class="clearfix"></div>
					</div>

					<div class="_footer-note">
						{if !isset($reference)}
						<p>
							{l s='Do not forget to insert your order number #%d in the subject of your bank wire.' sprintf=$id_order mod='bankbbl'}
						</p>
						{else}
						<p>
							{l s='Do not forget to insert your order reference %s in the subject of your bank wire.' sprintf=$reference mod='bankbbl'}
						</p>
						{/if}

						<p>
							{l s='Your order will be sent as soon as we receive payment.' mod='bankbbl'}
						</p>

						<p>
							{l s='If you have questions, comments or concerns, please contact our' mod='bankbbl'} <a href="{$link->getPageLink('contact', true)|escape:'html'}">{l s='expert customer support team' mod='bankbbl'}
						</p>
					</div>

				</div>
			</div>
		</div>
	</div>
{else}
	<p class="warning">
		{l s='We noticed a problem with your order. If you think this is an error, feel free to contact our' mod='bankbbl'} 
		<a href="{$link->getPageLink('contact', true)|escape:'html'}">{l s='expert customer support team' mod='bankbbl'}</a>.
	</p>
{/if}