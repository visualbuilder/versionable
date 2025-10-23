<?php

return [
    /*
     * Keep versions, you can redefine in target model.
     * Default: 0 - Keep all versions.
     */
    'keep_versions' => 0,

    /*
     * The model class for store versions.
     */
    'version_model' => \Visualbuilder\Versionable\Version::class,

    /**
     * Use uuid for version id.
     */
    'uuid' => false,
];
