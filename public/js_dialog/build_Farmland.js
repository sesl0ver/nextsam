// 다이얼로그
ns_dialog.dialogs.build_Farmland = new nsDialogSet('build_Farmland', 'dialog_building', 'size-large', { base_class: ns_dialog.dialogs.build_Template });

ns_button.buttons.build_Farmland_close = new nsButtonSet('build_Farmland_close', 'button_back', 'build_Farmland', { base_class: ns_button.buttons.common_close});
ns_button.buttons.build_Farmland_sub_close = new nsButtonSet('build_Farmland_sub_close', 'button_full', 'build_Farmland', { base_class: ns_button.buttons.common_sub_close});
//ns_button.buttons.build_Farmland_close_all = new nsButtonSet('build_Farmland_close_all', 'button_close_all', 'build_Farmland', { base_class: ns_button.buttons.common_close_all });

// ns_button.buttons.build_desc_Farmland = new nsButtonSet('build_desc_Farmland', 'button_text_style_desc', 'build_Farmland', { base_class: ns_button.buttons.build_desc });
ns_button.buttons.build_move_Farmland = new nsButtonSet('build_move_Farmland', 'button_middle_2', 'build_Farmland', { base_class: ns_button.buttons.build_move });
ns_button.buttons.build_cons_Farmland = new nsButtonSet('build_cons_Farmland', 'button_multi', 'build_Farmland', { base_class: ns_button.buttons.build_cons });
ns_button.buttons.build_upgrade_Farmland = new nsButtonSet('build_upgrade_Farmland', 'button_hero_action', 'build_Farmland', { base_class: ns_button.buttons.build_upgrade });

ns_button.buttons.build_prev_Farmland = new nsButtonSet('build_prev_Farmland', 'button_multi_prev', 'build_Farmland', { base_class: ns_button.buttons.build_prev });
ns_button.buttons.build_next_Farmland = new nsButtonSet('build_next_Farmland', 'button_multi_next', 'build_Farmland', { base_class: ns_button.buttons.build_next });

ns_button.buttons.build_speedup_Farmland = new nsButtonSet('build_speedup_Farmland', 'button_encourage', 'build_Farmland', { base_class: ns_button.buttons.build_speedup });
// ns_button.buttons.build_cancel_Farmland = new nsButtonSet('build_cancel_Farmland', 'button_build', 'build_Farmland', { base_class: ns_button.buttons.build_cancel });
