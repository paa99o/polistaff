from io import BytesIO
from pathlib import Path

from pypdf import PdfReader, PdfWriter
from reportlab.lib import colors
from reportlab.lib.enums import TA_CENTER
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.units import mm
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.platypus import (
    BaseDocTemplate, Frame, PageTemplate, Paragraph, Spacer, Table, TableStyle,
    PageBreak, KeepTogether, HRFlowable,
)

ROOT = Path(r"C:\laragon\www\polistaff")
SOURCE = Path(r"C:\Users\USER\OneDrive\Documents\user manual.pdf")
OUT = ROOT / "output" / "pdf" / "POLIBEST-Manual-Pengguna-Terkini-2026-10-03.pdf"
OUT.parent.mkdir(parents=True, exist_ok=True)

font_dir = Path(r"C:\Windows\Fonts")
pdfmetrics.registerFont(TTFont("Arial", str(font_dir / "arial.ttf")))
pdfmetrics.registerFont(TTFont("Arial-Bold", str(font_dir / "arialbd.ttf")))
pdfmetrics.registerFontFamily("Arial", normal="Arial", bold="Arial-Bold", italic="Arial", boldItalic="Arial-Bold")

GREEN = colors.HexColor("#4f8478")
INK = colors.HexColor("#26343e")
MUTED = colors.HexColor("#607487")
PALE = colors.HexColor("#edf4f1")
GOLD = colors.HexColor("#c68e4a")
LINE = colors.HexColor("#d8e2df")

styles = getSampleStyleSheet()
styles.add(ParagraphStyle(name="CoverTitleX", fontName="Arial-Bold", fontSize=24, leading=29, textColor=INK, spaceAfter=7))
styles.add(ParagraphStyle(name="EyebrowX", fontName="Arial-Bold", fontSize=8.5, leading=12, textColor=GREEN, tracking=1.3, spaceAfter=7))
styles.add(ParagraphStyle(name="BodyX", fontName="Arial", fontSize=9.4, leading=14, textColor=INK, spaceAfter=5))
styles.add(ParagraphStyle(name="SmallX", fontName="Arial", fontSize=8.1, leading=11, textColor=MUTED, spaceAfter=3))
styles.add(ParagraphStyle(name="HeadX", fontName="Arial-Bold", fontSize=14, leading=18, textColor=INK, spaceBefore=7, spaceAfter=5))
styles.add(ParagraphStyle(name="SubX", fontName="Arial-Bold", fontSize=10.5, leading=14, textColor=GREEN, spaceBefore=5, spaceAfter=3))
styles.add(ParagraphStyle(name="BulletX", fontName="Arial", fontSize=9.1, leading=13.2, textColor=INK, leftIndent=12, firstLineIndent=-8, bulletIndent=1, spaceAfter=3))
styles.add(ParagraphStyle(name="CalloutX", fontName="Arial", fontSize=8.8, leading=13, textColor=INK))

def P(text, style="BodyX"):
    return Paragraph(text, styles[style])

def bullet(text):
    return Paragraph("• " + text, styles["BulletX"])

def box(text, bg=PALE):
    t = Table([[P(text, "CalloutX")]], colWidths=[166*mm])
    t.setStyle(TableStyle([
        ("BACKGROUND", (0,0), (-1,-1), bg),
        ("BOX", (0,0), (-1,-1), .5, LINE),
        ("LINEBEFORE", (0,0), (0,-1), 3, GREEN),
        ("LEFTPADDING", (0,0), (-1,-1), 9), ("RIGHTPADDING", (0,0), (-1,-1), 9),
        ("TOPPADDING", (0,0), (-1,-1), 7), ("BOTTOMPADDING", (0,0), (-1,-1), 7),
    ]))
    return t

def footer(canvas, doc):
    canvas.saveState()
    w, h = A4
    canvas.setStrokeColor(LINE)
    canvas.setLineWidth(.55)
    canvas.line(22*mm, 15*mm, w-22*mm, 15*mm)
    canvas.setFont("Arial", 7.5)
    canvas.setFillColor(MUTED)
    canvas.drawString(22*mm, 10*mm, "POLIBEST · Manual Pengguna · Kemas kini 3 Oktober 2026")
    canvas.drawRightString(w-22*mm, 10*mm, f"Lampiran {doc.page}")
    canvas.restoreState()

buf = BytesIO()
doc = BaseDocTemplate(buf, pagesize=A4, leftMargin=22*mm, rightMargin=22*mm, topMargin=19*mm, bottomMargin=22*mm,
                      title="Manual Pengguna POLIBEST - Kemas Kini Oktober 2026", author="POLIBEST")
doc.addPageTemplates([PageTemplate(id="manual", frames=[Frame(22*mm, 22*mm, A4[0]-44*mm, A4[1]-41*mm, id="normal")], onPage=footer)])
story = []

# Page 1: changed public activity experience and publishing workflow.
story += [P("KEMAS KINI SISTEM · OKTOBER 2026", "EyebrowX"), P("Aktiviti komuniti", "CoverTitleX"),
          P("Cara melihat, menerbitkan dan mengurus aktiviti dalam portal POLIBEST.")]
story += [HRFlowable(width="100%", thickness=.8, color=LINE, spaceBefore=2, spaceAfter=9),
          P("1. Cari aktiviti", "HeadX"),
          P("Halaman <b>Aktiviti</b> memaparkan aktiviti yang telah diluluskan. Gunakan pilihan <b>Semua</b>, <b>Akan datang</b> atau <b>Telah dijalankan</b> untuk menapis senarai. Dalam paparan Semua, aktiviti akan datang disusun mengikut tarikh terdekat dan aktiviti lepas dipaparkan selepasnya, bermula dengan yang paling terkini."),
          P("Kad aktiviti pada halaman utama boleh digelongsorkan untuk melihat aktiviti lain. Pilih <b>Lihat aktiviti</b> untuk membuka butiran penuh.")]
story += [P("2. Daftar aktiviti baharu", "HeadX"),
          P("Borang penciptaan aktiviti menggunakan langkah berperingkat. Isi maklumat program, objektif, jadual, jawatankuasa dan bajet, kemudian semak sebelum dihantar. Antara maklumat yang diminta ialah nama program, penganjur, pegawai bertanggungjawab, tahap dan sesi program, ringkasan, kategori kursus, objektif, sasaran serta anggaran peserta, impak, tarikh dan masa, lokasi, tentatif, tetamu atau penceramah, jawatankuasa, sumber kewangan dan bajet."),
          P("Tarikh aktiviti yang telah berlalu juga boleh direkodkan. Permohonan tetap melalui semakan dan kelulusan seperti aktiviti lain. Aktiviti hanya dipaparkan kepada umum selepas diluluskan.")]
story += [box("Susunan kelulusan: permohonan dihantar → semakan Bendahari → semakan Admin → aktiviti diluluskan dan diterbitkan."),
          P("3. Semak butiran", "HeadX"),
          P("Halaman butiran memaparkan ringkasan program, status, tarikh, lokasi, penganjur, sasaran peserta, objektif, impak, tentatif dan maklumat berkaitan. Jika aktiviti mempunyai gambar, galeri gambar turut dipaparkan.")]
story.append(PageBreak())

# Page 2: post-event photo upload permissions and report PDF.
story += [P("PANDUAN PENCIPTA AKTIVITI", "EyebrowX"), P("Gambar selepas aktiviti", "CoverTitleX"),
          P("Gambar membantu warga POLIBEST melihat suasana dan hasil program.")]
story += [P("Muat naik gambar", "HeadX"),
          P("Selepas aktiviti selesai dan diluluskan, pencipta aktiviti boleh membuka halaman butiran aktiviti dan menggunakan bahagian galeri untuk menambah gambar. Fungsi ini hanya tersedia kepada pencipta aktiviti bagi aktiviti tersebut; ahli lain, Admin dan Bendahari tidak mendapat butang muat naik melalui halaman ini."),
          bullet("Muat naik sehingga 10 fail gambar untuk satu aktiviti."),
          bullet("Saiz maksimum ialah 8 MB bagi setiap gambar."),
          bullet("Gambar yang berjaya dimuat naik muncul pada halaman aktiviti awam dan paparan kad aktiviti yang berkaitan."),
          box("Jika butang muat naik belum kelihatan, pastikan anda log masuk menggunakan akaun pencipta aktiviti dan status aktiviti ialah diluluskan serta telah selesai."),
          P("Eksport laporan aktiviti", "HeadX"),
          P("Untuk aktiviti yang diluluskan dan telah selesai, buka butiran aktiviti dan pilih <b>Eksport Laporan &gt; PDF</b>. Laporan menggunakan maklumat yang diisi dalam borang serta rekod semasa, termasuk butiran program, tarikh dan lokasi, penganjur, jumlah peserta berdaftar dan kehadiran, objektif, impak, tentatif, jawatankuasa, tetamu atau penceramah, sumber kewangan dan bajet. Gambar aktiviti dan rekod kehadiran disertakan apabila tersedia."),
          box("Nama dokumen yang tepat ialah <b>Laporan Aktiviti</b>. Butang pada sesetengah paparan mungkin masih menggunakan label lama “Muat Turun Kertas Kerja”; fungsi PDF tersebut menjana laporan aktiviti." , bg=colors.HexColor("#fbf3e8")),
          P("Maklumat yang dipaparkan pada laporan bergantung pada butiran yang telah diisi dan rekod yang tersedia. Semak semula maklumat sebelum mengeksport.")]
story.append(PageBreak())

# Page 3: finance maintenance and roles.
story += [P("KEMAS KINI PENGURUSAN", "EyebrowX"), P("Kewangan dan rekod pengguna", "CoverTitleX"),
          P("Nota ringkas untuk peranan Bendahari dan Admin.")]
story += [P("Yuran dan tuntutan", "HeadX"),
          P("Kadar yuran semasa dipaparkan pada borang dan halaman pengurusan; rujuk kadar di dalam sistem kerana ia boleh berubah. Bendahari mengurus bil, pengesahan bayaran, sumbangan dan tuntutan mengikut akses yang diberikan."),
          P("Padam sejarah kewangan", "HeadX"),
          P("Hanya Bendahari mempunyai fungsi memadam rekod sejarah pembayaran yuran, sumbangan dan tuntutan. Pemadaman mengemas kini jumlah dan rekod berkaitan dalam sistem. Memadam rekod bil yuran yang telah dibayar membuang rekod pembayaran itu dan bil kembali menjadi belum dibayar; ia tidak memulangkan wang secara automatik ke akaun bank atau tunai staf."),
          box("Pemadaman sejarah kewangan adalah tindakan kekal. Pastikan rekod dan bukti berkaitan telah disemak sebelum memadam." , bg=colors.HexColor("#fbf3e8")),
          P("Pengurusan pengguna", "HeadX"),
          P("Admin boleh memadam akaun pengguna yang tidak berkaitan melalui pengurusan pengguna. Jika akaun dipadam, pengguna boleh memohon semula menggunakan alamat e-mel yang sama tertakluk pada keadaan akaun dan pengesahan semasa sistem."),
          P("Peranan dan kawalan akses", "HeadX"),
          P("Butang tindakan dipaparkan berdasarkan peranan. Ahli menggunakan fungsi umum dan fungsi berkaitan akaun sendiri; Bendahari mengurus rekod kewangan; Admin mengurus kelulusan dan akaun pengguna. Jika pilihan tidak kelihatan, semak peranan akaun atau hubungi pentadbir portal."),
          Spacer(1, 6), box("Sebelum demonstrasi, gunakan rekod yang telah disahkan dan akaun ujian yang sesuai. Elakkan memadam rekod sebenar untuk tujuan demo.")]
story.append(PageBreak())

# Page 4: PoliMart update appendix preserved in concise usable form.
story += [P("RUJUKAN PORTAL", "EyebrowX"), P("PoliMart", "CoverTitleX"),
          P("Panduan ringkas membeli-belah dan mengurus jualan dalam portal.")]
story += [P("Membeli melalui PoliMart", "HeadX"),
          P("Buka PoliMart untuk melihat produk yang tersedia. Pilih produk dan kuantiti, masukkan ke troli, kemudian semak butiran pesanan sebelum membuat pesanan. Ikuti arahan pembayaran yang dipaparkan dan semak status pesanan pada halaman berkaitan."),
          P("Mengurus jualan", "HeadX"),
          P("Pengguna yang mempunyai akses penjual boleh mengurus produk dan pesanan melalui halaman pengurusan yang disediakan. Pastikan maklumat produk, harga, stok dan arahan serahan dikemas kini. Proses pengesahan pesanan dan bayaran bergantung pada status serta arahan yang dipaparkan oleh sistem."),
          box("Paparan dan langkah PoliMart boleh berubah mengikut status produk, pesanan dan peranan pengguna. Ikut arahan terkini pada skrin portal."),
          P("Perkara yang dikemas kini dalam lampiran ini", "HeadX"),
          bullet("Senarai Aktiviti kini merangkumi aktiviti yang akan datang dan aktiviti yang telah dijalankan."),
          bullet("Aktiviti bertarikh lepas boleh direkodkan melalui borang aktiviti dan masih tertakluk pada kelulusan."),
          bullet("Muat naik gambar selepas aktiviti tersedia kepada pencipta aktiviti sahaja, selepas aktiviti diluluskan dan selesai."),
          bullet("PDF aktiviti ialah laporan yang berpandukan maklumat semasa dalam borang dan rekod sistem."),
          bullet("Kawalan pemadaman kewangan dan pengguna diterangkan mengikut peranan Bendahari dan Admin.")]
story.append(PageBreak())

# Page 5: version notes and screenshot recommendations.
story += [P("PENAMBAHBAIKAN MANUAL", "EyebrowX"), P("Tangkap layar yang disyorkan", "CoverTitleX"),
          P("Lampiran teks ini mengemas kini panduan, tetapi beberapa tangkap layar dalam halaman asal masih menunjukkan antaramuka lama. Gantikan selepas mengambil tangkap layar daripada sistem semasa.")]
rows = [
    [P("Keutamaan", "SubX"), P("Tangkap layar", "SubX"), P("Tujuan", "SubX")],
    [P("1", "BodyX"), P("Halaman <b>Semua Aktiviti</b> dengan pilihan Semua / Akan datang / Telah dijalankan dan contoh aktiviti lepas.", "SmallX"), P("Menunjukkan cara mencari aktiviti lama dan baharu.", "SmallX")],
    [P("2", "BodyX"), P("Butiran aktiviti yang telah selesai, termasuk bahagian muat naik dan galeri gambar.", "SmallX"), P("Menerangkan lokasi fungsi gambar dan hasil muat naik.", "SmallX")],
    [P("3", "BodyX"), P("Langkah borang penciptaan aktiviti terkini serta pratonton PDF Laporan Aktiviti.", "SmallX"), P("Menggantikan contoh borang dan laporan yang tidak lagi sepadan.", "SmallX")],
]
t = Table(rows, colWidths=[27*mm, 78*mm, 61*mm], repeatRows=1)
t.setStyle(TableStyle([
    ("BACKGROUND", (0,0), (-1,0), PALE), ("GRID", (0,0), (-1,-1), .45, LINE),
    ("VALIGN", (0,0), (-1,-1), "TOP"),
    ("LEFTPADDING", (0,0), (-1,-1), 7), ("RIGHTPADDING", (0,0), (-1,-1), 7),
    ("TOPPADDING", (0,0), (-1,-1), 7), ("BOTTOMPADDING", (0,0), (-1,-1), 7),
]))
story += [t, Spacer(1, 10), P("Halaman asal yang wajar disemak", "HeadX"),
          P("Bahagian aktiviti dalam manual asal—terutamanya tangkap layar borang penciptaan sekitar halaman 9–11—mungkin memaparkan medan atau susunan lama. Gantikan tangkap layar tersebut menggunakan tiga contoh di atas supaya manual tidak menunjukkan langkah yang sudah berubah."),
          P("Maklumat untuk pengguna", "HeadX"),
          P("Manual ini mengekalkan halaman asal dan menambah lampiran kemas kini bertarikh 3 Oktober 2026. Nama menu atau kedudukan butang boleh berubah sedikit apabila sistem dikemas kini. Rujuk arahan yang dipaparkan dalam portal jika berbeza.")]

doc.build(story)
appendix = PdfReader(buf)
original = PdfReader(str(SOURCE))
writer = PdfWriter()
for page in original.pages:
    writer.add_page(page)
for page in appendix.pages:
    writer.add_page(page)
writer.add_metadata({"/Title": "Manual Pengguna POLIBEST - Kemas Kini 3 Oktober 2026", "/Author": "POLIBEST"})
with OUT.open("wb") as f:
    writer.write(f)
print(f"Created {OUT} ({len(original.pages)} original pages + {len(appendix.pages)} update pages)")
