import json

transcript_path = r'C:\Users\HARI NANDHINI\.gemini\antigravity\brain\d38f07c8-fb35-43ff-bcdf-65c8e21ef140\.system_generated\logs\transcript_full.jsonl'
with open(transcript_path, 'r', encoding='utf-8') as f:
    for line in f:
        data = json.loads(line)
        content = data.get("content", "")
        if isinstance(content, str) and "PHASE 1 — REVALIDATE LIVE DATABASE" in content:
            print("Found Request 10! Length:", len(content))
            with open("scratch/request_10.txt", "w", encoding="utf-8") as out:
                out.write(content)
            print("Wrote request_10.txt successfully.")
            break
