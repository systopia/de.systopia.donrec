<?php
use CRM_Donrec_ExtensionUtil as E;

return [
  [
    'name' => 'OptionGroup_donrec_type',
    'entity' => 'OptionGroup',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'donrec_type',
        'title' => E::ts('type'),
        'option_value_fields' => [
          'name',
          'label',
          'description',
        ],
      ],
      'match' => [
        'name',
      ],
    ],
  ],
  [
    'name' => 'OptionGroup_donrec_type_OptionValue_SINGLE',
    'entity' => 'OptionValue',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'donrec_type',
        'label' => E::ts('single'),
        'value' => 'SINGLE',
        'name' => 'SINGLE',
      ],
      'match' => [
        'option_group_id',
        'name',
        'value',
      ],
    ],
  ],
  [
    'name' => 'OptionGroup_donrec_type_OptionValue_BULK',
    'entity' => 'OptionValue',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'donrec_type',
        'label' => E::ts('bulk'),
        'value' => 'BULK',
        'name' => 'BULK',
      ],
      'match' => [
        'option_group_id',
        'name',
        'value',
      ],
    ],
  ],
];
