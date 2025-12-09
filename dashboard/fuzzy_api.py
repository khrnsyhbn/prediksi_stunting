from unittest import result
from fastapi import FastAPI # type: ignore
from pydantic import BaseModel # type: ignore
import numpy as np # type: ignore
import skfuzzy as fuzz # type: ignore
from skfuzzy import control as ctrl # type: ignore
import datetime
from typing import List

app = FastAPI(title="Fuzzy Logic API for Stunting", version="2.0")

# --- Model request ---
class FuzzyInput(BaseModel):
    bbu: float
    tbu: float 
    bbtb: float

class BatchInput(BaseModel):
    data: List[FuzzyInput]

# -----------------------------
# Definisi variabel fuzzy
# ----------------------------- 
BB_U = ctrl.Antecedent(np.arange(-4, 4.1, 0.1), 'BB_U')
TB_U = ctrl.Antecedent(np.arange(-4, 4.1, 0.1), 'TB_U')
BB_TB = ctrl.Antecedent(np.arange(-4, 4.1, 0.1), 'BB_TB')
Level_Stunting = ctrl.Consequent(np.arange(0, 101, 1), 'Level_Stunting')

# -----------------------------
# Fungsi keanggotaan
# -----------------------------
BB_U['sangat_kurang'] = fuzz.trapmf(BB_U.universe, [-4,-4,-3,-2.75])
BB_U['kurang'] = fuzz.trimf(BB_U.universe, [-3.25,-2.5,-1.75])
BB_U['normal'] = fuzz.trimf(BB_U.universe, [-2.25,-0.5,1.25])
BB_U['risiko_berat_lebih'] = fuzz.trapmf(BB_U.universe, [0.75,1,4,4])

TB_U['sangat_pendek'] = fuzz.trapmf(TB_U.universe, [-4,-4,-3,-2.75])
TB_U['pendek'] = fuzz.trimf(TB_U.universe, [-3.25,-2.5,-1.75])
TB_U['normal'] = fuzz.trimf(TB_U.universe, [-2.25,0.5,3.25])
TB_U['tinggi'] = fuzz.trapmf(TB_U.universe, [2.75,3,4,4])

BB_TB['gizi_buruk'] = fuzz.trapmf(BB_TB.universe, [-4,-4,-3,-2.75])
BB_TB['gizi_kurang'] = fuzz.trimf(BB_TB.universe, [-3.25,-2.5,-1.75])
BB_TB['gizi_baik'] = fuzz.trimf(BB_TB.universe, [-2.25,-0.5,1.25])
BB_TB['risiko_gizi_lebih'] = fuzz.trimf(BB_TB.universe, [0.75,1.5,2.25])
BB_TB['gizi_lebih'] = fuzz.trimf(BB_TB.universe, [1.75,2.5,3.25])
BB_TB['obesitas'] = fuzz.trapmf(BB_TB.universe, [2.75,3,4,4])

Level_Stunting['stunting_parah'] = fuzz.trapmf(Level_Stunting.universe, [0, 0, 10, 25])
Level_Stunting['stunting_sedang'] = fuzz.trimf(Level_Stunting.universe, [20, 35, 50])
Level_Stunting['stunting_ringan'] = fuzz.trimf(Level_Stunting.universe, [45, 60, 75])
Level_Stunting['normal'] = fuzz.trimf(Level_Stunting.universe, [70, 85, 95])   # lebih luas
Level_Stunting['obesitas'] = fuzz.trapmf(Level_Stunting.universe, [90, 95, 100, 100])

# Level_Stunting['stunting_parah']  = fuzz.trapmf(Level_Stunting.universe, [0, 0, 5, 15])
# Level_Stunting['stunting_sedang'] = fuzz.trimf(Level_Stunting.universe, [10, 25, 40])
# Level_Stunting['stunting_ringan'] = fuzz.trimf(Level_Stunting.universe, [35, 50, 65])
# Level_Stunting['normal']          = fuzz.trimf(Level_Stunting.universe, [60, 75, 90])   # lebih luas
# Level_Stunting['obesitas']        = fuzz.trapmf(Level_Stunting.universe, [85, 92, 100, 100])

# -----------------------------
# Rule base sederhana (contoh)
# -----------------------------
# Format: (BB/U, TB/U, BB/TB, OUTPUT)
rule_data = [
    ('kurang','sangat_pendek','gizi_buruk','stunting_parah'),
    ('kurang','sangat_pendek','gizi_kurang','stunting_parah'),
    ('kurang','pendek','gizi_buruk','stunting_sedang'),
    ('kurang','sangat_pendek','gizi_baik','stunting_sedang'),
    ('kurang','pendek','gizi_kurang','stunting_sedang'),
    ('kurang','normal','gizi_buruk','stunting_ringan'),
    ('kurang','pendek','gizi_baik','stunting_ringan'),
    ('kurang','sangat_pendek','gizi_lebih','stunting_sedang'),
    ('kurang','normal','gizi_kurang','stunting_ringan'),
    ('kurang','tinggi','gizi_buruk','stunting_ringan'),
    ('kurang','sangat_pendek','risiko_gizi_lebih','stunting_sedang'),
    ('kurang','sangat_pendek','obesitas','stunting_sedang'),
    ('kurang','normal','gizi_baik','normal'),
    ('kurang','tinggi','gizi_kurang','stunting_ringan'),
    ('kurang','pendek','gizi_lebih','stunting_ringan'),
    ('kurang','pendek','risiko_gizi_lebih','stunting_ringan'),
    ('kurang','pendek','obesitas','stunting_ringan'),
    ('kurang','tinggi','gizi_baik','normal'),
    ('kurang','normal','gizi_lebih','obesitas'),
    ('kurang','normal','risiko_gizi_lebih','obesitas'),
    ('kurang','normal','obesitas','obesitas'),
    ('kurang','tinggi','gizi_lebih','obesitas'),
    ('kurang','tinggi','risiko_gizi_lebih','obesitas'),
    ('kurang','tinggi','obesitas','obesitas'),
    ('normal','sangat_pendek','gizi_buruk','stunting_parah'),
    ('normal','sangat_pendek','gizi_kurang','stunting_parah'),
    ('normal','pendek','gizi_buruk','stunting_sedang'),
    ('normal','sangat_pendek','gizi_baik','stunting_sedang'),
    ('normal','pendek','gizi_kurang','stunting_sedang'),
    ('normal','normal','gizi_buruk','stunting_ringan'),
    ('normal','pendek','gizi_baik','stunting_ringan'),
    ('normal','normal','gizi_kurang','stunting_ringan'),
    ('normal','sangat_pendek','gizi_lebih','stunting_sedang'),
    ('normal','tinggi','gizi_buruk','stunting_ringan'),
    ('normal','sangat_pendek','risiko_gizi_lebih','stunting_sedang'),
    ('normal','sangat_pendek','obesitas','stunting_sedang'),
    ('normal','normal','gizi_baik','normal'),
    ('normal','tinggi','gizi_kurang','stunting_ringan'),
    ('normal','pendek','gizi_lebih','stunting_ringan'),
    ('normal','pendek','risiko_gizi_lebih','stunting_ringan'),
    ('normal','pendek','obesitas','stunting_ringan'),
    ('normal','tinggi','gizi_baik','normal'),
    ('normal','normal','gizi_lebih','obesitas'),
    ('normal','normal','risiko_gizi_lebih','obesitas'),
    ('normal','normal','obesitas','obesitas'),
    ('normal','tinggi','gizi_lebih','obesitas'),
    ('normal','tinggi','risiko_gizi_lebih','obesitas'),
    ('normal','tinggi','obesitas','obesitas'),
    ('risiko_berat_lebih','sangat_pendek','gizi_buruk','stunting_parah'),
    ('risiko_berat_lebih','sangat_pendek','gizi_kurang','stunting_parah'),
    ('risiko_berat_lebih','pendek','gizi_buruk','stunting_sedang'),
    ('risiko_berat_lebih','sangat_pendek','gizi_baik','stunting_sedang'),
    ('risiko_berat_lebih','pendek','gizi_kurang','stunting_sedang'),
    ('risiko_berat_lebih','normal','gizi_buruk','stunting_ringan'),
    ('risiko_berat_lebih','pendek','gizi_baik','stunting_ringan'),
    ('risiko_berat_lebih','sangat_pendek','gizi_lebih','stunting_sedang'),
    ('risiko_berat_lebih','normal','gizi_kurang','stunting_ringan'),
    ('risiko_berat_lebih','tinggi','gizi_buruk','stunting_ringan'),
    ('risiko_berat_lebih','sangat_pendek','risiko_gizi_lebih','stunting_sedang'),
    ('risiko_berat_lebih','sangat_pendek','obesitas','stunting_sedang'),
    ('risiko_berat_lebih','normal','gizi_baik','normal'),
    ('risiko_berat_lebih','tinggi','gizi_kurang','stunting_ringan'),
    ('risiko_berat_lebih','pendek','gizi_lebih','stunting_ringan'),
    ('risiko_berat_lebih','pendek','risiko_gizi_lebih','stunting_ringan'),
    ('risiko_berat_lebih','pendek','obesitas','stunting_ringan'),
    ('risiko_berat_lebih','tinggi','gizi_baik','normal'),
    ('risiko_berat_lebih','normal','gizi_lebih','obesitas'),
    ('risiko_berat_lebih','normal','risiko_gizi_lebih','obesitas'),
    ('risiko_berat_lebih','normal','obesitas','obesitas'),
    ('risiko_berat_lebih','tinggi','gizi_lebih','obesitas'),
    ('risiko_berat_lebih','tinggi','risiko_gizi_lebih','obesitas'),
    ('risiko_berat_lebih','tinggi','obesitas','obesitas'),
    ('sangat_kurang','sangat_pendek','gizi_buruk','stunting_parah'),
    ('sangat_kurang','sangat_pendek','gizi_kurang','stunting_parah'),
    ('sangat_kurang','pendek','gizi_buruk','stunting_sedang'),
    ('sangat_kurang','sangat_pendek','gizi_baik','stunting_sedang'),
    ('sangat_kurang','pendek','gizi_kurang','stunting_sedang'),
    ('sangat_kurang','normal','gizi_buruk','stunting_ringan'),
    ('sangat_kurang','pendek','gizi_baik','stunting_ringan'),
    ('sangat_kurang','sangat_pendek','gizi_lebih','stunting_sedang'),
    ('sangat_kurang','normal','gizi_kurang','stunting_ringan'),
    ('sangat_kurang','tinggi','gizi_buruk','stunting_ringan'),
    ('sangat_kurang','sangat_pendek','risiko_gizi_lebih','stunting_sedang'),
    ('sangat_kurang','sangat_pendek','obesitas','stunting_sedang'),
    ('sangat_kurang','normal','gizi_baik','normal'),
    ('sangat_kurang','tinggi','gizi_kurang','stunting_ringan'),
    ('sangat_kurang','pendek','gizi_lebih','stunting_ringan'),
    ('sangat_kurang','pendek','risiko_gizi_lebih','stunting_ringan'),
    ('sangat_kurang','pendek','obesitas','stunting_ringan'),
    ('sangat_kurang','tinggi','gizi_baik','normal'),
    ('sangat_kurang','normal','gizi_lebih','obesitas'),
    ('sangat_kurang','normal','risiko_gizi_lebih','obesitas'),
    ('sangat_kurang','normal','obesitas','obesitas'),
    ('sangat_kurang','tinggi','gizi_lebih','obesitas'),
    ('sangat_kurang','tinggi','risiko_gizi_lebih','obesitas'),
    ('sangat_kurang','tinggi','obesitas','obesitas')
]

# ==============================================================
# PEMBENTUKAN RULE FUZZY
# ==============================================================

rules = []
for bb, tb, bt, out in rule_data:
    rules.append(ctrl.Rule(BB_U[bb] & TB_U[tb] & BB_TB[bt], Level_Stunting[out]))

    # ==============================================================
    # INFERENSI SISTEM KONTROL FUZZY
    # ============================================================== 

level_ctrl = ctrl.ControlSystem(rules) 

# --- Fungsi fuzzy ---
def hitung_fuzzy(raw_bbu, raw_tbu, raw_bbtb):
    stunting_sim = ctrl.ControlSystemSimulation(level_ctrl) # type: ignore

    # -----------------------------
    # -----------------------------
    # Sanitasi input agar tidak di luar range
    # -----------------------------
    def sanitize_input(val, min_val=-4, max_val=4):
        if val < min_val:
            return min_val, True  # True = disanitasi
        elif val > max_val:
            return max_val, True
        else:
            return val, False      # False = tidak disanitasi

    bb_u_val, bb_u_sanitized = sanitize_input(raw_bbu)
    tb_u_val, tb_u_sanitized = sanitize_input(raw_tbu)
    bb_tb_val, bb_tb_sanitized = sanitize_input(raw_bbtb)

    stunting_sim.input['BB_U'] = bb_u_val
    stunting_sim.input['TB_U'] = tb_u_val
    stunting_sim.input['BB_TB'] = bb_tb_val

    stunting_sim.compute()
    hasil = stunting_sim.output['Level_Stunting']

    
    # === 2️⃣ Ambil variabel output ===
    output_var = Level_Stunting  # variabel konsekuen

    # === 3️⃣ Hitung derajat keanggotaan crisp terhadap setiap label ===
    membership_values = {}
    for label, mf in output_var.terms.items():
        μ = fuzz.interp_membership(output_var.universe, mf.mf, hasil)
        membership_values[label] = μ

    label_akhir = max(membership_values, key=membership_values.get)

    # -----------------------------
    # Defuzzyfikasi ke kategori
    # -----------------------------

    # -----------------------------
    # Logging
    # -----------------------------
    timestamp = datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    with open("stunting_log.txt", "a", encoding="utf-8") as f:
        f.write(
            f"[{timestamp}] BB/U={raw_bbu}({'sanitized' if bb_u_sanitized else 'ok'}), "
            f"TB/U={raw_tbu}({'sanitized' if tb_u_sanitized else 'ok'}), "
            f"BB/TB={raw_bbtb}({'sanitized' if bb_tb_sanitized else 'ok'}), "
            f"Hasil={hasil:.2f}, Kategori={label_akhir.upper()}\n"
        )


    return {"Level Stunting": label_akhir.upper(), "Prediksi": round(hasil, 2)}

# --- fungsi mengambil kategori dari hasil fuzzyfikasi ---
def get_input_membership(z, var):
    """Mengembalikan dict membership {label: 0/1} berdasarkan nilai tertinggi."""
    memberships = {}
    for label, mf in var.terms.items():
        μ = fuzz.interp_membership(var.universe, mf.mf, z)
        memberships[label] = round(float(μ), 3)

    # Ambil label dengan membership tertinggi
    max_label = max(memberships, key=memberships.get)

    # Buat output: label tertinggi = 1, lainnya = 0
    final_dict = {label: (1 if label == max_label else 0) for label in memberships}

    return final_dict


# -----------------------------
# ENDPOINT MANUAL
# -----------------------------
@app.post("/stunting")
def stunting_endpoint(data: FuzzyInput):

    # 1. Hitung inferensi fuzzy
    hasil = hitung_fuzzy(data.bbu, data.tbu, data.bbtb)

    # 2. Ambil membership input BBU, TBU, BBTB
    membership_input = {
        "bbu": get_input_membership(data.bbu, BB_U),
        "tbu": get_input_membership(data.tbu, TB_U),
        "bbtb": get_input_membership(data.bbtb, BB_TB)
    }

    # 3. Return JSON lengkap
    return {
        "level_stunting": hasil["Level Stunting"],
        "prediksi": hasil["Prediksi"],
        "membership_input": membership_input
    }


# -----------------------------
# ENDPOINT BATCH
# -----------------------------
@app.post("/stunting/batch") 
def stunting_batch(input_data: BatchInput):
    results = []
    for item in input_data.data:
        hasil = hitung_fuzzy(item.bbu, item.tbu, item.bbtb)

        # Ambil membership input untuk saran gizi
        membership_input = {
            "bbu": get_input_membership(item.bbu, BB_U),
            "tbu": get_input_membership(item.tbu, TB_U),
            "bbtb": get_input_membership(item.bbtb, BB_TB)
        }

        results.append({
            "level_stunting": hasil["Level Stunting"],
            "prediksi": hasil["Prediksi"],
            "membership_input": membership_input
        })

    return {"total_data": len(results), "results": results}
