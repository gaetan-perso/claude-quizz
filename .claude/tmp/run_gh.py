import subprocess
import sys

GH = r"C:\Program Files\GitHub CLI\gh.exe"
OUT = r"C:\projets\IA\app_quizz_claude\.claude\tmp\gh_output.txt"

def run(args):
    try:
        r = subprocess.run([GH] + args, capture_output=True, text=True, timeout=30)
        return f"STDOUT: {r.stdout}\nSTDERR: {r.stderr}\nRC: {r.returncode}\n"
    except Exception as e:
        return f"EXCEPTION: {e}\n"

out = ""
out += "=== VERSION ===\n" + run(["--version"])
out += "=== AUTH STATUS ===\n" + run(["auth", "status"])
out += "=== ISSUE LIST ===\n" + run(["issue", "list", "--repo", "gaetan-perso/claude-quizz", "--limit", "50", "--state", "open", "--json", "number,title"])

with open(OUT, 'w') as f:
    f.write(out)

print("Done - wrote to", OUT)
