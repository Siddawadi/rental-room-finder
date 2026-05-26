import fs from "fs";
import path from "path";
import { Document, Packer, Paragraph, TextRun, PageBreak } from "docx";

const folderPath = "C:/XAMPP/htdocs/myproject"; // adjust if needed

function getAllFiles(dirPath, arrayOfFiles = []) {
  const files = fs.readdirSync(dirPath);
  for (const file of files) {
    const fullPath = path.join(dirPath, file);
    const stats = fs.statSync(fullPath);

    if (stats.isDirectory()) {
      if (file !== "node_modules" && file !== ".git") {
        getAllFiles(fullPath, arrayOfFiles);
      }
    } else {
      if (![".env", "package-lock.json"].includes(file)) {
        arrayOfFiles.push(fullPath);
      }
    }
  }
  return arrayOfFiles;
}

const files = getAllFiles(folderPath);
console.log(`🧾 Found ${files.length} files.`);

const docSections = [];

for (const filePath of files) {
  const content = fs.readFileSync(filePath, "utf8");

  docSections.push(
    new Paragraph({
      children: [
        new TextRun({ text: `📄 ${path.relative(folderPath, filePath)}`, bold: true, size: 28 }),
      ],
      spacing: { after: 200 },
    }),
    new Paragraph({
      children: [
        new TextRun({ text: content || "(empty file)", size: 22 }),
        new PageBreak(), // optional — start each file on a new page
      ],
    }),
    new Paragraph("")
  );
}

const doc = new Document({ sections: [{ children: docSections }] });

const outputPath = path.join(folderPath, "Project_Files.docx");

Packer.toBuffer(doc)
  .then((buffer) => {
    fs.writeFileSync(outputPath, buffer);
    console.log(`✅ Exported successfully to ${outputPath}`);
  })
  .catch((err) => console.error("❌ Error generating DOCX:", err));
