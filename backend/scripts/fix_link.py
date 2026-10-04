import os

path = r"d:\Project-dev\new-credit-score\frontend\app\Views\master\products.php"
with open(path, "rb") as f:
    raw = f.read()

target = b"<a href=\"<?= site_url('reports/products') ?>\" class=\"dropdown-action-item\">"
replacement = b"<a href=\"<?= site_url('reports/products') ?>?id=' + id + '\" class=\"dropdown-action-item\">"

print("Target in raw:", target in raw)
if target in raw:
    new_raw = raw.replace(target, replacement)
    with open(path, "wb") as f:
        f.write(new_raw)
    print("Successfully replaced!")
else:
    print("Target not found directly in raw bytes. Searching for similar bytes...")
    idx = raw.find(b"reports/products")
    print("Found 'reports/products' at:", idx)
    if idx != -1:
        print("Surrounding:", raw[max(0, idx-50):min(len(raw), idx+100)])
