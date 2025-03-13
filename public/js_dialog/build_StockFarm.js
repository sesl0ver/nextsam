// 다이얼로그
ns_dialog.dialogs.build_StockFarm = new nsDialogSet('build_StockFarm', 'dialog_building', 'size-large', { base_class: ns_dialog.dialogs.build_Template });

ns_button.buttons.build_StockFarm_close = new nsButtonSet('build_StockFarm_close', 'button_back', 'build_StockFarm', { base_class: ns_button.buttons.common_close});
ns_button.buttons.build_StockFarm_sub_close = new nsButtonSet('build_StockFarm_sub_close', 'button_full', 'build_StockFarm', { base_class: ns_button.buttons.common_sub_close});
//ns_button.buttons.build_StockFarm_close_all = new nsButtonSet('build_StockFarm_close_all', 'button_close_all', 'build_StockFarm', { base_class: ns_button.buttons.common_close_all });

// ns_button.buttons.build_desc_StockFarm = new nsButtonSet('build_desc_StockFarm', 'button_text_style_desc', 'build_StockFarm', { base_class: ns_button.buttons.build_desc });
ns_button.buttons.build_move_StockFarm = new nsButtonSet('build_move_StockFarm', 'button_middle_2', 'build_StockFarm', { base_class: ns_button.buttons.build_move });
ns_button.buttons.build_cons_StockFarm = new nsButtonSet('build_cons_StockFarm', 'button_multi', 'build_StockFarm', { base_class: ns_button.buttons.build_cons });
ns_button.buttons.build_upgrade_StockFarm = new nsButtonSet('build_upgrade_StockFarm', 'button_hero_action', 'build_StockFarm', { base_class: ns_button.buttons.build_upgrade });

ns_button.buttons.build_prev_StockFarm = new nsButtonSet('build_prev_StockFarm', 'button_multi_prev', 'build_StockFarm', { base_class: ns_button.buttons.build_prev });
ns_button.buttons.build_next_StockFarm = new nsButtonSet('build_next_StockFarm', 'button_multi_next', 'build_StockFarm', { base_class: ns_button.buttons.build_next });

ns_button.buttons.build_speedup_StockFarm = new nsButtonSet('build_speedup_StockFarm', 'button_encourage', 'build_StockFarm', { base_class: ns_button.buttons.build_speedup });
// ns_button.buttons.build_cancel_StockFarm = new nsButtonSet('build_cancel_StockFarm', 'button_build', 'build_StockFarm', { base_class: ns_button.buttons.build_cancel });
