/*
 Navicat Premium Dump SQL

 Source Server         : DEV - 40.22
 Source Server Type    : PostgreSQL
 Source Server Version : 140017 (140017)
 Source Host           : localhost:5432
 Source Catalog        : db_smartloannextgeneration
 Source Schema         : public

 Target Server Type    : PostgreSQL
 Target Server Version : 140017 (140017)
 File Encoding         : 65001

 Date: 29/09/2026 10:28:15
*/


-- ----------------------------
-- Table structure for cfg_agama
-- ----------------------------
DROP TABLE IF EXISTS "public"."cfg_agama";
CREATE TABLE "public"."cfg_agama" (
  "agamaid" int2 NOT NULL,
  "agamanm" varchar(15) COLLATE "pg_catalog"."default"
)
;

-- ----------------------------
-- Records of cfg_agama
-- ----------------------------
INSERT INTO "public"."cfg_agama" VALUES (1, 'Islam');
INSERT INTO "public"."cfg_agama" VALUES (2, 'Protestan');
INSERT INTO "public"."cfg_agama" VALUES (3, 'Katolik');
INSERT INTO "public"."cfg_agama" VALUES (4, 'Buddha');
INSERT INTO "public"."cfg_agama" VALUES (5, 'Hindu');
INSERT INTO "public"."cfg_agama" VALUES (6, 'Konghucu');
INSERT INTO "public"."cfg_agama" VALUES (7, 'Kepercayaan');

-- ----------------------------
-- Indexes structure for table cfg_agama
-- ----------------------------
CREATE INDEX "idx_cfg_agama" ON "public"."cfg_agama" USING btree (
  "agamanm" COLLATE "pg_catalog"."default" "pg_catalog"."text_ops" ASC NULLS LAST
);

-- ----------------------------
-- Primary Key structure for table cfg_agama
-- ----------------------------
ALTER TABLE "public"."cfg_agama" ADD CONSTRAINT "cfg_agama_pkey" PRIMARY KEY ("agamaid");
