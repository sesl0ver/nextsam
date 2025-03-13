// 다이얼로그
ns_dialog.dialogs.build_Cottage = new nsDialogSet('build_Cottage', 'dialog_building', 'size-large', { base_class: ns_dialog.dialogs.build_Template });

ns_button.buttons.build_Cottage_close = new nsButtonSet('build_Cottage_close', 'button_back', 'build_Cottage', { base_class: ns_button.buttons.common_close});
ns_button.buttons.build_Cottage_sub_close = new nsButtonSet('build_Cottage_sub_close', 'button_full', 'build_Cottage', { base_class: ns_button.buttons.common_sub_close});
//ns_button.buttons.build_Cottage_close_all = new nsButtonSet('build_Cottage_close_all', 'button_close_all', 'build_Cottage', { base_class: ns_button.buttons.common_close_all });

// ns_button.buttons.build_desc_Cottage = new nsButtonSet('build_desc_Cottage', 'button_text_style_desc', 'build_Cottage', { base_class: ns_button.buttons.build_desc });
ns_button.buttons.build_move_Cottage = new nsButtonSet('build_move_Cottage', 'button_middle_2', 'build_Cottage', { base_class: ns_button.buttons.build_move });
ns_button.buttons.build_cons_Cottage = new nsButtonSet('build_cons_Cottage', 'button_multi', 'build_Cottage', { base_class: ns_button.buttons.build_cons });
ns_button.buttons.build_upgrade_Cottage = new nsButtonSet('build_upgrade_Cottage', 'button_hero_action', 'build_Cottage', { base_class: ns_button.buttons.build_upgrade });

ns_button.buttons.build_prev_Cottage = new nsButtonSet('build_prev_Cottage', 'button_multi_prev', 'build_Cottage', { base_class: ns_button.buttons.build_prev });
ns_button.buttons.build_next_Cottage = new nsButtonSet('build_next_Cottage', 'button_multi_next', 'build_Cottage', { base_class: ns_button.buttons.build_next });

ns_button.buttons.build_speedup_Cottage = new nsButtonSet('build_speedup_Cottage', 'button_encourage', 'build_Cottage', { base_class: ns_button.buttons.build_speedup });
// ns_button.buttons.build_cancel_Cottage = new nsButtonSet('build_cancel_Cottage', 'button_build', 'build_Cottage', { base_class: ns_button.buttons.build_cancel });
