import subprocess
import os
import sys

OUT = r"C:\projets\IA\app_quizz_claude\.claude\tmp\gh_output2.txt"

# Try different gh paths
gh_paths = [
    r"C:\Program Files\GitHub CLI\gh.exe",
    r"C:\Users\gaeta\AppData\Local\GitHub CLI\gh.exe",
    "gh",
]

results = []

for gh in gh_paths:
    try:
        r = subprocess.run(
            [gh, "--version"],
            capture_output=True, text=True, timeout=10,
            shell=False
        )
        results.append(f"Found gh at: {gh}\nVersion: {r.stdout}\nStderr: {r.stderr}\n")
    except FileNotFoundError:
        results.append(f"Not found: {gh}\n")
    except Exception as e:
        results.append(f"Error with {gh}: {e}\n")

out = "\n".join(results)

try:
    with open(OUT, 'w') as f:
        f.write(out)
except Exception as e:
    # Try alternate write location
    with open(r"C:\projets\IA\app_quizz_claude\.claude\tmp\gh_output2.txt", 'w') as f:
        f.write(out + f"\nWrite error: {e}")

sys.stdout.write("Script done\n")
sys.stdout.flush()
