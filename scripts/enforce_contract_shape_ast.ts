import fs from "fs";
import path from "path";
import { Project } from "ts-morph";

type ContractRule = {
  file: string;
  exports: {
    name: string;
    kind: "interface" | "type";
    requiredFields: string[];
    optionalFields: string[];
  }[];
};

type Registry = { protectedContracts: ContractRule[] };

function fail(msg: string): never {
  console.error("AST CONTRACT FAILURE:", msg);
  process.exit(1);
}

const registryPath = path.join(process.cwd(), "contracts", "protected-contracts.json");
if (!fs.existsSync(registryPath)) {
  console.log("No protected-contracts.json found; skipping");
  process.exit(0);
}

const registry = JSON.parse(fs.readFileSync(registryPath, "utf8")) as Registry;
const project = new Project({ skipAddingFilesFromTsConfig: true });

for (const rule of registry.protectedContracts) {
  const filePath = path.join(process.cwd(), rule.file);
  if (!fs.existsSync(filePath)) fail(`Missing protected contract file: ${rule.file}`);
  const source = project.addSourceFileAtPath(filePath);

  for (const exp of rule.exports) {
    if (exp.kind === "interface") {
      const iface = source.getInterface(exp.name);
      if (!iface) fail(`Missing interface export: ${exp.name} in ${rule.file}`);
      const props = iface.getProperties();
      const names = new Map(props.map(p => [p.getName(), p]));
      for (const field of exp.requiredFields) {
        const prop = names.get(field);
        if (!prop) fail(`Missing required field ${field} in ${exp.name}`);
        if (prop.hasQuestionToken()) fail(`Required field became optional: ${field} in ${exp.name}`);
      }
      for (const field of exp.optionalFields) {
        const prop = names.get(field);
        if (!prop) fail(`Missing optional field ${field} in ${exp.name}`);
        if (!prop.hasQuestionToken()) fail(`Optional field became required: ${field} in ${exp.name}`);
      }
    } else {
      const alias = source.getTypeAlias(exp.name);
      if (!alias) fail(`Missing type export: ${exp.name} in ${rule.file}`);
    }
  }
}

console.log("AST contract validation passed");
