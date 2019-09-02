DELETE FROM `wms_goodsbatch` WHERE `gb_code` = 'GB40519180021538';
DELETE FROM `wms_goodstored` WHERE `gs_code` = 'GW40519180021867';
DELETE FROM `wms_igoods` WHERE `igo_code` = 'GO40519180021635';
DELETE FROM `wms_igoodsent` WHERE `igo_code` = 'GO40519180021635' AND `gs_code` = 'GW40519180021867';
UPDATE `wms_goods` SET `goods_init`=`goods_init`-40.00,`goods_count`=`goods_count`-0 WHERE `spu_code`='PD40423080419556' AND `war_code`='WA100028';
DELETE FROM `wms_goodsbatch` WHERE `gb_code` = 'GB40519180020504';
DELETE FROM `wms_goodstored` WHERE `gs_code` = 'GW40519180020648';
DELETE FROM `wms_igoods` WHERE `igo_code` = 'GO40519180021515';
DELETE FROM `wms_igoodsent` WHERE `igo_code` = 'GO40519180021515' AND `gs_code` = 'GW40519180020648';
UPDATE `wms_goods` SET `goods_init`=`goods_init`-45.00,`goods_count`=`goods_count`-0 WHERE `spu_code`='SP000832' AND `war_code`='WA100028'