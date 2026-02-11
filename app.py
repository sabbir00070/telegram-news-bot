from flask import Flask
import subprocess

app = Flask(__name__)

@app.route("/")
def run_bot():
    subprocess.run(["python3", "bot.py"])
    return "Bot executed"

if __name__ == "__main__":
    app.run()