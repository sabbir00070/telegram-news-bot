import requests
import json
import os
import re

BOT_TOKEN = "8227631061:AAGOFVi-R2f-1JZWl73wPMyOAoF8krNNaUk"
CHAT_ID = "-1003553733506"
API_URL = "https://news-api-kohl-delta.vercel.app/jamuna.tv"
STORAGE = "sent.json"


# Create storage file if not exists
if not os.path.exists(STORAGE):
    with open(STORAGE, "w") as f:
        json.dump([], f)

# Load sent IDs
with open(STORAGE, "r") as f:
    try:
        sent = json.load(f)
    except:
        sent = []

if not isinstance(sent, list):
    sent = []


# Fetch news API
try:
    response = requests.get(API_URL, timeout=20)
    data = response.json()
except Exception as e:
    print("API fetch failed:", e)
    exit()

if not data.get("success") or not data.get("news"):
    print("Invalid API response")
    exit()


for item in data["news"]:

    news_id = item.get("id")

    if news_id in sent:
        continue

    title = item.get("title", "").strip()
    photo = item.get("image", "")
    reporter = item.get("reporter", "").strip()
    time = item.get("time", "").replace("প্রকাশ: ", "").strip()
    url = item.get("url", "")
    body = item.get("body", "")

    # Remove HTML tags but keep spacing
    body = re.sub('<[^<]+?>', '', body)
    body = body.strip()

    # Remove signature like /এমএইচআর (optional safe cleanup)
    body = re.sub(r'/[^\s]+$', '', body).strip()

    # Limit length (Telegram caption max 1024)
    body = body[:800]

    # Build caption (same design)
    caption = f"<b>{title}</b>\n\n"
    caption += f"🕒 {time}\n"
    caption += f"👤 {reporter}\n\n"
    caption += f"{body}"

    send_url = f"https://api.telegram.org/bot{BOT_TOKEN}/sendPhoto"

    keyboard = {
        "inline_keyboard": [
            [
                {"text": "🌐 Visit Website", "url": url}
            ]
        ]
    }

    payload = {
        "chat_id": CHAT_ID,
        "photo": photo,
        "caption": caption,
        "parse_mode": "HTML",
        "reply_markup": json.dumps(keyboard)
    }

    try:
        r = requests.post(send_url, data=payload, timeout=20)
        result = r.json()

        if result.get("ok"):
            sent.append(news_id)
            print("Posted:", title)
        else:
            print("Telegram error:", result)

    except Exception as e:
        print("Telegram send failed:", e)


# Save sent IDs
with open(STORAGE, "w") as f:
    json.dump(sent, f)

print("Completed")