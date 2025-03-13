// 다이얼로그
ns_dialog.dialogs.build_CityHall = new nsDialogSet('build_CityHall', 'dialog_building', 'size-large', { base_class: ns_dialog.dialogs.build_Template });

ns_button.buttons.build_CityHall_close = new nsButtonSet('build_CityHall_close', 'button_back', 'build_CityHall', { base_class: ns_button.buttons.common_close });
ns_button.buttons.build_CityHall_sub_close = new nsButtonSet('build_CityHall_sub_close', 'button_full', 'build_CityHall', { base_class: ns_button.buttons.common_sub_close });
//ns_button.buttons.build_CityHall_close_all = new nsButtonSet('build_CityHall_close_all', 'button_close_all', 'build_CityHall', { base_class: ns_button.buttons.common_close_all });

// ns_button.buttons.build_desc_CityHall = new nsButtonSet('build_desc_CityHall', 'button_text_style_desc', 'build_CityHall', { base_class: ns_button.buttons.build_desc });
ns_button.buttons.build_assign_CityHall = new nsButtonSet('build_assign_CityHall', 'button_full', 'build_CityHall', { base_class: ns_button.buttons.build_assign });
ns_button.buttons.build_upgrade_CityHall = new nsButtonSet('build_upgrade_CityHall', 'button_hero_action', 'build_CityHall', { base_class: ns_button.buttons.build_upgrade} );

ns_button.buttons.build_speedup_CityHall = new nsButtonSet('build_speedup_CityHall', 'button_encourage', 'build_CityHall', { base_class: ns_button.buttons.build_speedup });
ns_button.buttons.build_cancel_CityHall = new nsButtonSet('build_cancel_CityHall', 'button_build', 'build_CityHall', { base_class: ns_button.buttons.build_cancel });

