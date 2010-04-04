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
 * A singletone service that manages changesets within the system.
 *
 * Use methods on this object to create and retrieve changesets, add new items to the current user's changeset,
 * move items between changesets, and submit changesets. 
 *
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 */
class ChangesetService
{
	/**
	 * Default empty constructor
	 */
	public function __construct() {

	}

	/**
	 * Create a new changeset for the given member (the current user is used as the default)
	 *
	 * @param String $name
	 *			A name for this changeset, if applicable
	 * @return
	 *			The new changeset object
	 */
    public function createChangeset($name = '', $member = null) {
		if (!$member) {
			$member = Member::currentUser();
		}
		$changeset = new ContentChangeset();
		$changeset->Title = $name;
		$changeset->OwnerID = $member->ID;
		$changeset->write();

		return $changeset;
	}

	/**
	 * Gets the current changeset for this user if it exists
	 *
	 * @param Member $member
	 *
	 * @return ContentChangeset
	 */
	public function getChangesetForUser($member = null) {
		if (!$member) {
			$member = Member::currentUser();
		}

		if ($member == null) {
			throw new Exception("User not logged in");
		}

		$filter = db_quote(array(
			'OwnerID =' => $member->ID,
			'Status =' => 'Active',
		));

		// we just want to get the first changeset
		$changeset = DataObject::get_one('ContentChangeset', $filter);
		return $changeset;
	}

	/**
	 * Gets the current changeset for a given content item
	 *
	 * @param SiteTree $object
	 */
	public function getChangesetForContent(SiteTree $object) {
		$filter = db_quote(array(
			'Status =' => 'Active',
		));

		$changesets = $object->Changesets($filter);

		if ($changesets) {
			return $changesets->First();
		}
	}

	/**
	 * Submit changeset to the published site
	 *
	 * @param ContentChangeset $changeset
	 */
	public function submitChangeset(ContentChangeset $changeset) {
		
	}

	/**
	 * Add some content into a changeset. 
	 *
	 * @param SiteTree $object
	 * @param ContentChangeset $changeset
	 */
	public function addContentToChangeset(SiteTree $object, ContentChangeset $changeset) {
		$changeset->Items()->add($object);
	}

	/**
	 * Revert an object in a changeset. First, the item is removed from the changeset. Then,
	 * it is reverted to the current public version, or if it is NEW content, revert
	 *
	 * @param SiteTree $object
	 *			The object to remove
	 */
	public function revertFromChangeset(SiteTree $object, ContentChangeset $changeset) {
		$changeset->Items()->remove($object);

		if ($object->ExistsOnLive) {
			$object->doRevertToLive();
		} else {
			// we should just delete it then?
			$object->delete();
		}
	}
}
?>