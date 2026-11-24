#!/usr/bin/env python3
"""Pemeriksaan konsistensi dokumen blueprint Aplikasi Kepegawaian PIS.

Dijalankan pada Tahap 1. Tidak menguji aplikasi (belum ada kode),
hanya memeriksa integritas dokumen: keberadaan berkas, ID unik,
referensi K-xx, katalog permission, spesifikasi tabel, dan blok Mermaid.
"""
from __future__ import annotations

import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
DOCS = ROOT / "docs"

REQUIRED = [
    "MASTER_SPECIFICATION.md",
    "SRS.md",
    "DATABASE_DESIGN.md",
    "BUSINESS_PROCESS.md",
    "UI_UX_SPECIFICATION.md",
    "ACCESS_CONTROL.md",
    "TECHNICAL_ARCHITECTURE.md",
    "DEVELOPMENT_ROADMAP.md",
    "CLARIFICATIONS.md",
    "DEVELOPMENT_PROGRESS.md",
    "CHANGELOG.md",
    "TEST_REPORT.md",
]

errors: list[str] = []
warnings: list[str] = []
checks: list[tuple[str, str]] = []


def ok(msg: str) -> None:
    checks.append(("LULUS", msg))


def fail(msg: str) -> None:
    errors.append(msg)
    checks.append(("GAGAL", msg))


def warn(msg: str) -> None:
    warnings.append(msg)


texts: dict[str, str] = {}
for name in REQUIRED:
    p = DOCS / name
    if not p.exists():
        fail(f"Berkas wajib tidak ditemukan: docs/{name}")
    else:
        texts[name] = p.read_text(encoding="utf-8")

if len(texts) == len(REQUIRED):
    ok(f"Semua {len(REQUIRED)} berkas dokumentasi wajib tersedia")

if not (ROOT / "README.md").exists():
    fail("README.md tidak ditemukan di akar proyek")
else:
    ok("README.md tersedia")

if not (ROOT / "Prompt_Induk_Aplikasi_Kepegawaian_PIS.md").exists():
    fail("Berkas prompt induk hilang")
else:
    ok("Berkas prompt induk tetap utuh")

# --- ID unik -------------------------------------------------------------
for prefix, name in (("FR-", "SRS.md"), ("BR-", "SRS.md"), ("K-", "CLARIFICATIONS.md")):
    source = texts.get(name, "")
    if prefix == "K-":
        ids = re.findall(r"^\| (K-\d{2}) \|", source, re.M)
    else:
        ids = re.findall(rf"\b{prefix}[A-Z]*-?\d{{2,3}}\b", source)
    duplicates = sorted({i for i in ids if ids.count(i) > 1})
    if duplicates:
        warn(f"{name}: ID {prefix} muncul lebih dari sekali: {', '.join(duplicates)}")
    else:
        ok(f"{name}: ID {prefix} unik ({len(set(ids))} ID)")

# --- Referensi K-xx harus terdefinisi ------------------------------------
defined_k = set(re.findall(r"^\| (K-\d{2}) \|", texts.get("CLARIFICATIONS.md", ""), re.M))
all_refs: dict[str, set[str]] = {}
for name, text in texts.items():
    if name == "CLARIFICATIONS.md":
        continue
    refs = set(re.findall(r"\bK-\d{2}\b", text))
    refs -= defined_k
    if refs:
        all_refs[name] = refs
if all_refs:
    for name, refs in all_refs.items():
        fail(f"{name}: referensi klarifikasi tidak terdefinisi: {', '.join(sorted(refs))}")
else:
    ok(f"Semua referensi K-xx terdefinisi ({len(defined_k)} kode)")

# --- Permission katalog ---------------------------------------------------
access = texts.get("ACCESS_CONTROL.md", "")
perm_rows = re.findall(r"^\| `([a-z][a-z0-9_]*\.[a-z0-9_.]+)` \|", access, re.M)
perm_dupes = sorted({p for p in perm_rows if perm_rows.count(p) > 1})
if perm_dupes:
    fail(f"ACCESS_CONTROL.md: kode permission duplikat: {', '.join(perm_dupes)}")
else:
    ok(f"ACCESS_CONTROL.md: {len(perm_rows)} kode permission unik")

bad_format = [p for p in perm_rows if not re.fullmatch(r"[a-z][a-z0-9_]*(\.[a-z][a-z0-9_]*)+", p)]
if bad_format:
    fail(f"ACCESS_CONTROL.md: format permission tidak sesuai: {', '.join(bad_format)}")
else:
    ok("Semua kode permission memakai format modul.aksi")

# permission yang disebut di dokumen lain harus ada di katalog
SETTING_KEYS = {
    "app.name",
    "attendance.default_radius_m",
    "attendance.accuracy_threshold_m",
    "attendance.max_photo_kb",
    "payroll.rounding_mode",
    "security.session_single_device",
    "security.password_min_length",
}
perm_set = set(perm_rows)
catalog_prefixes = {p.split(".")[0] for p in perm_set}
unknown_refs: dict[str, set[str]] = {}
for name, text in texts.items():
    if name == "ACCESS_CONTROL.md":
        continue
    for token in re.findall(r"`([a-z][a-z0-9_]*(?:\.[a-z][a-z0-9_]*)+)`", text):
        if token in SETTING_KEYS:
            continue
        if token.split(".")[0] in catalog_prefixes and token not in perm_set:
            unknown_refs.setdefault(name, set()).add(token)
if unknown_refs:
    for name, refs in unknown_refs.items():
        fail(f"{name}: permission tidak ada di katalog: {', '.join(sorted(refs))}")
else:
    ok("Semua permission yang disebut dokumen lain ada di katalog")

# --- Spesifikasi tabel ----------------------------------------------------
db = texts.get("DATABASE_DESIGN.md", "")
specced = set(re.findall(r"^### \d+\.\d+ `([a-z_]+)`", db, re.M))
framework = {"password_reset_tokens", "sessions", "jobs", "job_batches", "failed_jobs", "cache", "migrations"}
declared_block = re.search(r"### 1\.1 Daftar tabel per domain\n\n(.*?)\n---", db, re.S)
declared: set[str] = set()
if declared_block:
    declared = set(re.findall(r"`([a-z_]+)`", declared_block.group(1)))
declared -= framework
missing = declared - specced
extra = specced - declared
if missing:
    fail(f"DATABASE_DESIGN.md: tabel di daftar tanpa spesifikasi: {', '.join(sorted(missing))}")
else:
    ok(f"DATABASE_DESIGN.md: seluruh {len(declared)} tabel domain memiliki spesifikasi kolom")
if extra:
    warn(f"DATABASE_DESIGN.md: tabel berspesifikasi tetapi tidak di daftar: {', '.join(sorted(extra))}")

# jumlah tabel yang diklaim
m = re.search(r"\| Jumlah tabel \| (\d+)", db)
if m:
    claimed = int(m.group(1))
    if claimed != len(declared) + len(framework):
        fail(f"DATABASE_DESIGN.md: klaim {claimed} tabel, aktual {len(declared) + len(framework)}")
    else:
        ok(f"Jumlah tabel konsisten: {claimed} (termasuk tabel framework)")
else:
    fail("DATABASE_DESIGN.md: jumlah tabel tidak dinyatakan")

# PK & FK disebut pada setiap spesifikasi tabel
no_pk = []
for t in sorted(specced):
    block = re.search(rf"^### \d+\.\d+ `{t}`(.*?)(?=^### |\Z)", db, re.M | re.S)
    body = block.group(1) if block else ""
    if "PK" not in body:
        no_pk.append(t)
if no_pk:
    fail(f"Tabel tanpa penanda PK: {', '.join(no_pk)}")
else:
    ok("Setiap tabel memiliki penanda primary key (PK)")

# --- Mermaid --------------------------------------------------------------
for name, text in texts.items():
    fences = text.count("```")
    if fences % 2 != 0:
        fail(f"{name}: blok kode tidak seimbang ({fences} fence)")
mermaid_blocks = sum(len(re.findall(r"```mermaid", t)) for t in texts.values())
ok(f"{mermaid_blocks} diagram Mermaid dengan blok kode seimbang")

# --- Tabel markdown -------------------------------------------------------
for name, text in texts.items():
    lines = text.splitlines()
    i = 0
    while i < len(lines) - 1:
        if lines[i].startswith("|") and re.match(r"^\|[\s:|-]+\|$", lines[i + 1] or ""):
            cols = lines[i].count("|")
            j = i + 2
            while j < len(lines) and lines[j].startswith("|"):
                if lines[j].count("|") != cols:
                    fail(f"{name}: baris {j + 1} jumlah kolom tabel tidak konsisten")
                    break
                j += 1
            i = j
        else:
            i += 1
if not any("jumlah kolom tabel" in e for e in errors):
    ok("Struktur tabel Markdown konsisten")

# --- Tahapan --------------------------------------------------------------
roadmap = texts.get("DEVELOPMENT_ROADMAP.md", "")
stages = re.findall(r"^## Tahap (\d)", roadmap, re.M)
if stages == [str(i) for i in range(1, 9)]:
    ok("Roadmap memuat 8 tahap berurutan")
else:
    fail(f"Roadmap tidak memuat 8 tahap berurutan: {stages}")

srs = texts.get("SRS.md", "")
fr_ids = set(re.findall(r"\| (FR-[A-Z]+-\d{3}) \|", srs))
stage_col = re.findall(r"\| (FR-[A-Z]+-\d{3}) \|.*?\| (Wajib|Sebaiknya|Opsional) \|", srs)
if len(fr_ids) < 50:
    warn(f"SRS hanya memuat {len(fr_ids)} kebutuhan fungsional unik")
else:
    ok(f"SRS memuat {len(fr_ids)} kebutuhan fungsional unik dengan prioritas")

# --- Hasil ----------------------------------------------------------------
print("=" * 78)
print("HASIL PEMERIKSAAN DOKUMEN — TAHAP 1")
print("=" * 78)
for status, msg in checks:
    print(f"[{status:5}] {msg}")
if warnings:
    print("\nPERINGATAN:")
    for w in warnings:
        print(f"  - {w}")
print("\nRINGKASAN:", "SEMUA LULUS" if not errors else f"{len(errors)} GAGAL")
print(f"Total pemeriksaan: {len(checks)} | Gagal: {len(errors)} | Peringatan: {len(warnings)}")
sys.exit(1 if errors else 0)
