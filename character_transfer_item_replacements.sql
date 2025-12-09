/*
 Navicat Premium Data Transfer

 Source Server         : Localhost mysql
 Source Server Type    : MySQL
 Source Server Version : 100428
 Source Host           : localhost:3306
 Source Schema         : fusiongen

 Target Server Type    : MySQL
 Target Server Version : 100428
 File Encoding         : 65001

 Date: 22/02/2024 12:12:19
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for character_transfer_item_replacements
-- ----------------------------
DROP TABLE IF EXISTS `character_transfer_item_replacements`;
CREATE TABLE `character_transfer_item_replacements`  (
  `itemid` int NOT NULL,
  `replacementitemid` int NULL DEFAULT NULL,
  `class` int NULL DEFAULT NULL,
  `faction` int NULL DEFAULT NULL,
  `note` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  PRIMARY KEY (`itemid`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of character_transfer_item_replacements
-- ----------------------------
INSERT INTO `character_transfer_item_replacements` VALUES (47131, 47115, NULL, NULL, NULL);
INSERT INTO `character_transfer_item_replacements` VALUES (49623, 50815, NULL, NULL, NULL);
INSERT INTO `character_transfer_item_replacements` VALUES (49998, 50764, NULL, NULL, NULL);
INSERT INTO `character_transfer_item_replacements` VALUES (50402, 49949, NULL, NULL, NULL);
INSERT INTO `character_transfer_item_replacements` VALUES (50455, 50455, NULL, NULL, NULL);
INSERT INTO `character_transfer_item_replacements` VALUES (50619, 50188, NULL, NULL, NULL);
INSERT INTO `character_transfer_item_replacements` VALUES (50633, 50421, NULL, NULL, NULL);
INSERT INTO `character_transfer_item_replacements` VALUES (50688, 50413, NULL, NULL, NULL);
INSERT INTO `character_transfer_item_replacements` VALUES (51275, 51164, NULL, NULL, NULL);
INSERT INTO `character_transfer_item_replacements` VALUES (51277, 51162, NULL, NULL, NULL);
INSERT INTO `character_transfer_item_replacements` VALUES (51278, 51161, NULL, NULL, NULL);
INSERT INTO `character_transfer_item_replacements` VALUES (51279, 51160, NULL, NULL, NULL);
INSERT INTO `character_transfer_item_replacements` VALUES (52019, 52019, NULL, NULL, NULL);
INSERT INTO `character_transfer_item_replacements` VALUES (52572, 49985, NULL, NULL, NULL);
INSERT INTO `character_transfer_item_replacements` VALUES (54578, 53125, NULL, NULL, NULL);
INSERT INTO `character_transfer_item_replacements` VALUES (54580, 53126, NULL, NULL, NULL);
INSERT INTO `character_transfer_item_replacements` VALUES (54590, 54569, NULL, NULL, NULL);

SET FOREIGN_KEY_CHECKS = 1;
