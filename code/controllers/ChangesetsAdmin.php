<?php
/*

Copyright (c) 2009, SilverStripe Australia PTY LTD - www.silverstripe.com.au
All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:

    * Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the
      documentation and/or other materials provided with the distribution.
    * Neither the name of SilverStripe nor the names of its contributors may be used to endorse or promote products derived from this software
      without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE
GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT,
STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY
OF SUCH DAMAGE.
*/

/**
 * Admin controller for changesets
 *
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 */
class ChangesetsAdmin extends LeftAndMain
{
    static $url_segment = 'changeset-admin';

	static $url_rule = '$Action//$ID';

	static $menu_title = 'Changes';

	public static $allowed_actions = array(
		'showchangeset',
		'submitall',
		'revertall'
	);

	/**
	 *
	 * @var ChangesetService
	 */
	protected $changesetService;

	/**
	 * Get the list of changesets available to this user
	 */
	public function Changesets() {
		$changesets = singleton('ChangesetService')->getAvailableChangesets();
		return $changesets;
	}

	public function EditForm($request=null, $vars=null) {
		$forUser = Member::currentUser();
		$cid = $this->request->param('ID');
		$changeset = null;
		if ($cid) {
			$changeset = singleton('ChangesetService')->getChangeset($cid);
		} else {
			$changeset = singleton('ChangesetService')->getChangesetForUser();
			if (!$changeset) {
				// just get any one
				$possibles = singleton('ChangesetService')->getAvailableChangesets();
				if ($possibles) {
					$changeset = $possibles->First();
				}
			}
		}
		
		$form = null;

		if ($changeset) {
			$tableFields = array(
				"Title" => _t('Changesets.PAGE_TITLE', 'Title'),
				"LastEdited" => _t('Changesets.LAST_EDITED', 'Last Edited'),
				'ChangeType' => _t('Changesets.CHANGE_TYPE', 'Type of Change')
			);

			$popupFields = new FieldSet(
				new TextField('Name', _t('CommentAdmin.NAME', 'Name')),
				new TextField('CommenterURL', _t('CommentAdmin.COMMENTERURL', 'URL'))
			);

			$idField = new HiddenField('ID', '', $changeset->ID);
		
			$table = new ComplexTableField($this, "Changes", "SiteTree", $tableFields);
			$table->setParentClass(false);
			$table->setFieldCasting(array(
				'LastEdited' => 'SSDatetime->Nice'
			));

			$table->setCustomSourceItems($changeset->Changes());

			$fields = new FieldSet(
				new TabSet(	'Root',
					new Tab(_t('Changesets.CHANGESETS', 'Changesets'),
						new LiteralField("Title", $changeset->Title . ' (' . $changeset->Owner()->Email.')'),
						$idField,
						$table
					)
				)
			);

			$actions = new FieldSet();

			$actions->push(new FormAction('submitall', _t('Changesets.SUBMIT_ALL', 'Submit All Changes')));
			$actions->push(new FormAction('revertall', _t('Changesets.REVERT_ALL', 'Revert All Changes')));

			$form = new Form($this, "EditForm", $fields, $actions);
		}
		
		return $form;
	}

	/**
	 * Gets the changes for a particular user
	 */
	public function showchangeset() {
		return $this->renderWith('ChangesetsAdmin_right');
	}

	/**
	 * Submits all the items in the currently selected changeset
	 */
	public function submitall($params=null, $form=null) {
		$cid = isset($params['ID']) ? $params['ID'] : null;
		$changeset = null;
		if (!$cid) {
			throw new Exception("Invalid Changeset");
		}

		$changeset = singleton('ChangesetService')->getChangeset($cid);
		if ($changeset) {
			$changeset->submit();
			FormResponse::status_message(sprintf(_t('Changesets.SUBMITTED_CHANGESET', 'Submitted content in changeset %s'), $changeset->Title), 'good');
		} else {
			FormResponse::status_message(sprintf(_t('Changesets.CHANGESET_NOT_FOUND', 'Could not find changeset')), 'bad');
		}

		return FormResponse::respond();
	}

	/**
	 * Revert all edits for a particular changeset
	 */
	public function revertall($params=null, $form=null) {
		$cid = isset($params['ID']) ? $params['ID'] : null;
		$changeset = null;
		if (!$cid) {
			throw new Exception("Invalid Changeset");
		}

		$changeset = singleton('ChangesetService')->getChangeset($cid);
		if ($changeset) {
			$changeset->revertAll();
			FormResponse::status_message(sprintf(_t('Changesets.REVERTED_ALL', 'Reverted content in changeset %s'), $changeset->Title), 'good');
		} else {
			FormResponse::status_message(sprintf(_t('Changesets.CHANGESET_NOT_FOUND', 'Could not find changeset')), 'bad');
		}

		return FormResponse::respond();
	}
}
?>