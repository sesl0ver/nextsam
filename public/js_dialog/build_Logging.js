// 다이얼로그
ns_dialog.dialogs.build_Logging = new nsDialogSet('build_Logging', 'dialog_building', 'size-large', { base_class: ns_dialog.dialogs.build_Template });

ns_button.buttons.build_Logging_close = new nsButtonSet('build_Logging_close', 'button_back', 'build_Logging', { base_class: ns_button.buttons.common_close});
ns_button.buttons.build_Logging_sub_close = new nsButtonSet('build_Logging_sub_close', 'button_full', 'build_Logging', { base_class: ns_button.buttons.common_sub_close});
ns_button.buttons.build_Logging_close_all = new nsButtonSet('build_Logging_close_all', 'button_close_all', 'build_Logging', { base_class: ns_button.buttons.common_close_all });

// ns_button.buttons.build_desc_Logging = new nsButtonSet('build_desc_Logging', 'button_text_style_desc', 'build_Logging', { base_class: ns_button.buttons.build_desc });
ns_button.buttons.build_move_Logging = new nsButtonSet('build_move_Logging', 'button_middle_2', 'build_Logging', { base_class: ns_button.buttons.build_move });
ns_button.buttons.build_cons_Logging = new nsButtonSet('build_cons_Logging', 'button_multi', 'build_Logging', { base_class: ns_button.buttons.build_cons });
ns_button.buttons.build_upgrade_Logging = new nsButtonSet('build_upgrade_Logging', 'button_hero_action', 'build_Logging', { base_class: ns_button.buttons.build_upgrade });

ns_button.buttons.build_prev_Logging = new nsButtonSet('build_prev_Logging', 'button_multi_prev', 'build_Logging', { base_class: ns_button.buttons.build_prev });
ns_button.buttons.build_next_Logging = new nsButtonSet('build_next_Logging', 'button_multi_next', 'build_Logging', { base_class: ns_button.buttons.build_next });

ns_button.buttons.build_speedup_Logging = new nsButtonSet('build_speedup_Logging', 'button_encourage', 'build_Logging', { base_class: ns_button.buttons.build_speedup });
// ns_button.buttons.build_cancel_Logging = new nsButtonSet('build_cancel_Logging', 'button_build', 'build_Logging', { base_class: ns_button.buttons.build_cancel });
