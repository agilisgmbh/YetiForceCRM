{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
	<div class="pdfTemplateContents leftRightPadding3p">
		<form name="EditPdfTemplate" action="index.php" method="post" id="pdf_step8" class="form-horizontal">
			<input type="hidden" name="module" value="PDF">
			<input type="hidden" name="action" value="Save">
			<input type="hidden" name="parent" value="Settings">
			<input type="hidden" class="step" value="8" />
			<input type="hidden" name="record" value="{$RECORDID}" />

			<div class="padding1per stepBorder">
				<label>
					<strong>{vtranslate('LBL_STEP_N',$QUALIFIED_MODULE, 8)}: {vtranslate('LBL_ENTER_BASIC_DETAILS',$QUALIFIED_MODULE)}</strong>
				</label>
				<br>
				<div class="form-group">
					<label class="col-sm-3 control-label">
						{vtranslate('LBL_DESCRIPTION', $QUALIFIED_MODULE)}
					</label>
					<div class="col-sm-6 controls">
						<input type="text" name="colg" class="form-control" value="{$PDF_MODEL->get('colg')}" id="colg" />
					</div>
				</div>
			</div>
			<br>
			<div class="pull-right">
				<button class="btn btn-danger backStep" type="button"><strong>{vtranslate('LBL_BACK', $QUALIFIED_MODULE)}</strong></button>&nbsp;&nbsp;
				<button class="btn btn-success" type="submit"><strong>{vtranslate('LBL_FINISH', $QUALIFIED_MODULE)}</strong></button>
			</div>
		</form>
	</div>
{/strip}