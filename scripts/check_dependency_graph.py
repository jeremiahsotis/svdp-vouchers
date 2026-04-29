import pathlib
import re
import sys

states = {}
for sf in pathlib.Path("docs/roadmap").glob("v*/roadmap-state.md"):
    text = sf.read_text(errors="ignore")
    for line in text.splitlines():
        m = re.match(r"\|\s*([^|]+?)\s*\|\s*([^|]+?)\s*\|", line)
        if m and m.group(1).lower() != "slice":
            states[m.group(1).strip()] = m.group(2).strip()

for df in pathlib.Path("docs/roadmap").glob("v*/dependency-graph.md"):
    for line in df.read_text(errors="ignore").splitlines():
        if "→" in line:
            child, dep = [s.strip() for s in line.split("→", 1)]
            if states.get(child) == "In Progress" and states.get(dep) != "Complete":
                print(f"Dependency violation: {child} in progress but {dep} is {states.get(dep, 'missing')}")
                sys.exit(1)

print("Dependency graph check passed")
