<?php

//************ */ 1. Register the last login date, time, and count the number of logins.
function record_last_login_time($user_login, $user) {
  // Update last login time
  update_user_meta($user->ID, 'last_login', current_time('mysql'));

  // Count number of logins
  $login_count = get_user_meta($user->ID, 'login_count', true);
  if (!$login_count) {
      $login_count = 0;
  }
  update_user_meta($user->ID, 'login_count', ++$login_count);
}
add_action('wp_login', 'record_last_login_time', 10, 2);

//************ */ 2. Add columns to display the last login date and the number of logins.
function add_custom_user_columns($columns) {
  $columns['last_login'] = __('Last Login');
  $columns['login_count'] = __('Login Count');
  return $columns;
}
add_filter('manage_users_columns', 'add_custom_user_columns');

//************ */ 3. Display the last login date and number of logins in the new columns.
function display_custom_user_columns($value, $column_name, $user_id) {
  if ($column_name == 'last_login') {
      $last_login = get_user_meta($user_id, 'last_login', true);
      if ($last_login) {
          return date('d/m/Y H:i:s', strtotime($last_login)); // Latin format: day/month/year
      } else {
          return __('Never');
      }
  }

  if ($column_name == 'login_count') {
      $login_count = get_user_meta($user_id, 'login_count', true);
      if ($login_count) {
          return $login_count;
      } else {
          return __('0');
      }
  }

  return $value;
}
add_action('manage_users_custom_column', 'display_custom_user_columns', 10, 3);

//************ */ 4. Make both columns sortable.
function make_custom_user_columns_sortable($columns) {
  $columns['last_login'] = 'last_login';
  $columns['login_count'] = 'login_count';
  return $columns;
}
