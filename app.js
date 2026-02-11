export default async function handler(req, res) {
  const BOT_TOKEN = process.env.BOT_TOKEN;
  const CHAT_ID = process.env.CHAT_ID;
  const API_URL = "https://news-api-kohl-delta.vercel.app/jamuna.tv";

  try {
    const response = await fetch(API_URL);
    const data = await response.json();

    if (!data.success || !data.news) {
      return res.status(400).json({ error: "Invalid API response" });
    }

    for (const item of data.news.slice(0, 1)) { // send only latest 1
      const title = item.title?.trim() || "";
      const photo = item.image || "";
      const reporter = item.reporter?.trim() || "";
      const time = item.time?.replace("প্রকাশ: ", "").trim() || "";
      const url = item.url || "";
      let body = item.body || "";

      body = body.replace(/<[^>]*>?/gm, "").trim();
      body = body.substring(0, 800);

      const caption =
        `<b>${title}</b>\n\n` +
        `🕒 ${time}\n` +
        `👤 ${reporter}\n\n` +
        `${body}`;

      const sendUrl = `https://api.telegram.org/bot${BOT_TOKEN}/sendPhoto`;

      await fetch(sendUrl, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          chat_id: CHAT_ID,
          photo: photo,
          caption: caption,
          parse_mode: "HTML",
          reply_markup: {
            inline_keyboard: [
              [{ text: "🌐 Visit Website", url: url }]
            ]
          }
        })
      });
    }

    return res.status(200).json({ success: true });
  } catch (error) {
    return res.status(500).json({ error: error.message });
  }
}