<?php

use CRM_Dedupetools_ExtensionUtil as E;

class CRM_Dedupetools_BAO_MergeConflict extends CRM_Dedupetools_DAO_MergeConflict {

  /**
   * Get boolean fields that may be involved in merges.
   *
   * These are fields which can be resolved by forcing to no or yes.
   *
   * @throws \CiviCRM_API3_Exception
   */
  public static function getBooleanFields() {
    $booleanFields = [];
    $fields = civicrm_api3('Contact', 'getfields', [])['values'];
    $emailFields = civicrm_api3('Email', 'getfields', ['action' => 'create'])['values'];
    $ignoreList = ['skip_greeting_processing', 'is_primary', 'is_deleted', 'contact_is_deleted', 'dupe_check', 'uf_user'];
    foreach (array_merge($fields, $emailFields) as $fieldName => $fieldSpec) {
      if (!in_array($fieldName, $ignoreList)
        && isset($fieldSpec['type'])
        && (
          // As of CiviCRM 5.20 on_hold is a boolean field unless civimail_multiple_bulk_emails
          // is enabled, at which point it becomes a 3-way toggle (with theory being that opt_out is
          // set per email rather than per contact so we get 0 = no, 1 = bounce (regardless)
          // and then 2 = opt out IF the setting is in play.
          $fieldSpec['type'] === CRM_Utils_Type::T_BOOLEAN
          || ($fieldName === 'on_hold' && !Civi::settings()->get('civimail_multiple_bulk_emails'))
        )
      ) {
        $prefix = CRM_Utils_Array::value('entity', $fieldSpec) === 'Email' ? E::ts('Email::') : '';
        $booleanFields[$fieldSpec['name']] = $prefix . $fieldSpec['title'];
      }
    }
    return $booleanFields;

  }

  /**
   * Get Contact fields as a name => title array.
   *
   * @return array
   *
   * @throws \CiviCRM_API3_Exception
   */
  public static function getContactFields() {
    $fields = civicrm_api3('Contact', 'getfields', ['action' => 'create'])['values'];
    $generalFields = [];
    foreach ($fields as $field) {
      $generalFields[$field['name']] = $field['title'];
    }
    return $generalFields;
  }

  /**
   * Get the criteria for determining the contact whose data should be preferred.
   *
   * @return array
   */
  public static function getPreferredContactCriteria(): array {
    return [
      'most_recently_created_contact' => E::ts('More recently created contact'),
      'earliest_created_contact' => E::ts('Less recently created contact'),
      'most_recently_modified_contact' => E::ts('More recently modified contact'),
      'earliest_modified_contact' => E::ts('Less recently modified contact'),
      'most_recent_contributor' => E::ts('Contact with most recent contribution.'),
      'most_prolific_contributor' => E::ts('Contact with most contributions.'),
    ];
  }

  /**
   * Get the criteria for determining the contact whose data should be preferred if other methods fail.
   *
   * @return array
   */
  public static function getPreferredContactCriteriaFallback(): array {
    return array_intersect_key(self::getPreferredContactCriteria(),
      array_fill_keys([
        'most_recently_created_contact',
        'earliest_created_contact'
      ], 1)
    );
  }

  /**
   * Get the criteria for determining the contact whose data should be preferred.
   *
   * @return array
   */
  public static function getEquivalentNameOptions(): array {
    return [
      'prefer_nick_name' => E::ts('Prefer nick name, discard conflicting name'),
      'prefer_non_nick_name' => E::ts('Prefer non-nick name, discard conflicting name'),
      'prefer_non_nick_name_keep_nick_name' => E::ts('Prefer non-nick name, put nick-name in nick name field'),
      'prefer_preferred_contact_value' => E::ts('Prefer value from preferred contact (eg most recent donor), discard conflicting value'),
      'prefer_preferred_contact_value_keep_nick_name' => E::ts('Prefer value from preferred contact, put nick name, if exists in nick name field'),
    ];
  }
}
