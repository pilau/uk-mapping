UK Mapping
=================

A WordPress plugin for managing UK postcodes and local authority areas data.

## Installation

Note that the plugin folder should be named `uk-mapping`. This is because if the [GitHub Updater plugin](https://github.com/afragen/github-updater) is used to update this plugin, if the folder is named something other than this, it will get deleted, and the updated plugin folder with a different name will cause the plugin to be silently deactivated.

## Basic usage

## Filter hooks
* `pukm_post_type_args_postcode_area` - Filter array arguments for defining postcode area custom post type
* `pukm_post_type_args_postcode_dis` - Filter array arguments for defining postcode district custom post type
* `pukm_post_type_args_postcode_sect` - Filter array arguments for defining postcode sector custom post type
* `pukm_post_type_args_postcode_unit` - Filter array arguments for defining postcode unit custom post type

## Action hooks
