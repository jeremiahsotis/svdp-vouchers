import fs from "fs";
import path from "path";
import { execSync } from "child_process";

const changed = execSync("git diff --name-only HEAD~1..HEAD", { encoding: "utf8" })
  .split(/\r?\n/)
  .map(s => s.trim())
  .filter(Boolean);

const registryPath = path.join(process.cwd(), "contracts", "protected-surfaces.json");
const acceptancePath = path.join(process.cwd(), "contracts", "protected-surface-acceptance.json");

const registry = fs.existsSync(registryPath) ? JSON.parse(fs.readFileSync(registryPath, "utf8")) : { protectedSurfaces: [] };
const acceptance = fs.existsSync(acceptancePath) ? JSON.parse(fs.readFileSync(acceptancePath, "utf8")) : { acceptedFindings: [] };

const registered = new Set((registry.protectedSurfaces || []).map((x: any) => x.path.replace(/\\/g, "/")));
const accepted = new Set((acceptance.acceptedFindings || []).map((x: any) => x.path.replace(/\\/g, "/")));

const heuristics = [
  { test: (p: string, c: string) => /auth|session|token|authorize|authenticate/i.test(p + " " + c), surfaceType: "auth_boundary" },
  { test: (p: string, c: string) => /policy|permission|access|canAccess|isAllowed|scopeResolution/i.test(p + " " + c), surfaceType: "policy_engine" },
  { test: (p: string, c: string) => /reducer|action\.type|state machine/i.test(p + " " + c), surfaceType: "state_machine" },
  { test: (p: string, c: string) => /timeline_events|event_store|append-only/i.test(p + " " + c), surfaceType: "append_only" },
  { test: (p: string, c: string) => /contracts|export interface|export type/i.test(p + " " + c), surfaceType: "contract_surface" },
];

const findings: string[] = [];

for (const file of changed) {
  if (!fs.existsSync(file) || fs.statSync(file).isDirectory()) continue;
  const content = fs.readFileSync(file, "utf8");
  for (const rule of heuristics) {
    if (rule.test(file, content)) {
      const normalized = file.replace(/\\/g, "/");
      if (!registered.has(normalized) && !accepted.has(normalized)) {
        findings.push(`${file} -> ${rule.surfaceType}`);
      }
      break;
    }
  }
}

if (findings.length) {
  console.error("Unregistered protected surfaces detected:");
  for (const f of findings) console.error(`- ${f}`);
  process.exit(1);
}

console.log("Protected surface auto-detection passed");
