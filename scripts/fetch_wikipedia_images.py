import requests
import mysql.connector
import time
import json

# -----------------------------
# DB CONNECTION
# -----------------------------
db = mysql.connector.connect(
    host="localhost",
    user="root",
    password="",
    database="vehicle_personality_matcher"
)
cursor = db.cursor(dictionary=True)

# -----------------------------
# WIKI CONFIG
# -----------------------------
API = "https://en.wikipedia.org/w/api.php"
HEADERS = {
    "User-Agent": "VehiclePersonalityMatcher/1.0 (educational project)"
}

def safe_get_json(response, context):
    if response.status_code != 200:
        print(f"[HTTP {response.status_code}] {context}")
        return None

    if not response.text or not response.text.strip():
        print(f"[EMPTY RESPONSE] {context}")
        return None

    try:
        return response.json()
    except json.JSONDecodeError:
        print(f"[INVALID JSON] {context}")
        return None


def get_page_title(make, model):
    params = {
        "action": "query",
        "list": "search",
        "srsearch": f"{make} {model}",
        "format": "json"
    }

    r = requests.get(API, params=params, headers=HEADERS, timeout=10)
    data = safe_get_json(r, f"search: {make} {model}")
    if not data:
        return None

    results = data.get("query", {}).get("search", [])
    if results:
        return results[0]["title"]

    return None


def get_image_titles(page_title):
    params = {
        "action": "query",
        "titles": page_title,
        "prop": "images",
        "format": "json"
    }

    r = requests.get(API, params=params, headers=HEADERS, timeout=10)
    data = safe_get_json(r, f"images list: {page_title}")
    if not data:
        return []

    pages = data.get("query", {}).get("pages", {})
    for page in pages.values():
        return [img["title"] for img in page.get("images", [])]

    return []


def resolve_image_url(image_title):
    params = {
        "action": "query",
        "titles": image_title,
        "prop": "imageinfo",
        "iiprop": "url",
        "format": "json"
    }

    r = requests.get(API, params=params, headers=HEADERS, timeout=10)
    data = safe_get_json(r, f"resolve image: {image_title}")
    if not data:
        return None

    pages = data.get("query", {}).get("pages", {})
    for page in pages.values():
        info = page.get("imageinfo")
        if info:
            return info[0]["url"]

    return None


def get_car_images(make, model, max_images=3):
    title = get_page_title(make, model)
    if not title:
        return []

    image_titles = get_image_titles(title)

    images = []
    for img in image_titles:
        img_l = img.lower()

        # filter junk
        if not img_l.endswith((".jpg", ".jpeg", ".png")):
            continue
        if any(x in img_l for x in ["logo", "badge", "icon", "symbol"]):
            continue

        url = resolve_image_url(img)
        if url:
            images.append(url)

        if len(images) >= max_images:
            break

    return images


# -----------------------------
# MAIN LOOP
# -----------------------------
cursor.execute("SELECT id, make, model FROM vehicle")
vehicles = cursor.fetchall()

print(f"Processing {len(vehicles)} vehicles")

for v in vehicles:
    urls = get_car_images(v["make"], v["model"])

    if not urls:
        print(f"✘ No images for {v['make']} {v['model']}")
        continue

    for url in urls:
        cursor.execute(
            """
            INSERT IGNORE INTO vehicle_images (vehicle_id, image_url)
            VALUES (%s, %s)
            """,
            (v["id"], url)
        )

    db.commit()
    print(f"✔ {len(urls)} images added for {v['make']} {v['model']}")

    time.sleep(1)  # polite rate limit

cursor.close()
db.close()
