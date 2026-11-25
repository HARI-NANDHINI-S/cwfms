import json

transcript_path = r'C:\Users\HARI NANDHINI\.gemini\antigravity\brain\d38f07c8-fb35-43ff-bcdf-65c8e21ef140\.system_generated\logs\transcript.jsonl'
with open(transcript_path, 'r', encoding='utf-8') as f:
    lines = f.readlines()

for line in lines:
    data = json.loads(line)
    step = data.get("step_index")
    if step in (2460, 2461, 2463, 2464, 2465, 2466):
        print(f"--- STEP {step} ---")
        print(f"Tool calls: {data.get('tool_calls')}")
        print(f"Content: {data.get('content')}")
