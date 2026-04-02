<?php
use CRM_Donrec_ExtensionUtil as E;

return [
  [
    'name' => 'OptionGroup_donrec_status',
    'entity' => 'OptionGroup',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'donrec_status',
        'title' => E::ts('status'),
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
    'name' => 'OptionGroup_donrec_status_OptionValue_ORIGINAL',
    'entity' => 'OptionValue',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'donrec_status',
        'label' => E::ts('original'),
        'value' => 'ORIGINAL',
        'name' => 'ORIGINAL',
      ],
      'match' => [
        'option_group_id',
        'name',
        'value',
      ],
    ],
  ],
  [
    'name' => 'OptionGroup_donrec_status_OptionValue_COPY',
    'entity' => 'OptionValue',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'donrec_status',
        'label' => E::ts('copy'),
        'value' => 'COPY',
        'name' => 'COPY',
      ],
      'match' => [
        'option_group_id',
        'name',
        'value',
      ],
    ],
  ],
  [
    'name' => 'OptionGroup_donrec_status_OptionValue_WITHDRAWN',
    'entity' => 'OptionValue',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'donrec_status',
        'label' => E::ts('withdrawn'),
        'value' => 'WITHDRAWN',
        'name' => 'WITHDRAWN',
      ],
      'match' => [
        'option_group_id',
        'name',
        'value',
      ],
    ],
  ],
  [
    'name' => 'OptionGroup_donrec_status_OptionValue_WITHDRAWN_COPY',
    'entity' => 'OptionValue',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'donrec_status',
        'label' => E::ts('withdrawn_copy'),
        'value' => 'WITHDRAWN_COPY',
        'name' => 'WITHDRAWN_COPY',
      ],
      'match' => [
        'option_group_id',
        'name',
        'value',
      ],
    ],
  ],
];
