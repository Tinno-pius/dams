<?php
/**
 * Helper data for the Digital RCH4 card (Phase 7).
 */
require_once __DIR__ . '/../../config/database.php';

/**
 * All checkbox columns of the risk_assessment table, grouped by section.
 * The value is the human label shown on the card.
 */
function rch4_risk_fields()
{
    return [
        'A' => [
            'a_age_below_20'         => 'Umri chini ya miaka 20',
            'a_ten_years_gap'        => 'Miaka 10 au zaidi tokea mimba ya mwisho',
            'a_previous_caesarean'   => 'Kuzaa kwa kupasuliwa',
            'a_previous_stillbirth'  => 'Kuzaa mtoto mfu / kifo cha mtoto mchanga',
            'a_multiple_miscarriage' => 'Kuharibika kwa mimba 2 au zaidi',
            'a_heart_disease'        => 'Ugonjwa wa Moyo',
            'a_diabetes'             => 'Kisukari',
            'a_tuberculosis'         => 'Kifua Kikuu',
        ],
        'B' => [
            'b_fourth_pregnancy'       => 'Mimba ya 4 au zaidi',
            'b_first_above_35'         => 'Mimba ya kwanza zaidi ya miaka 35',
            'b_height_below_150'       => 'Kimo chini ya CM 150',
            'b_previous_vacuum'        => 'Kuzalishwa kwa kupasuliwa au vakum',
            'b_pelvic_deformity'       => 'Kilema cha nyonga',
            'b_postpartum_haemorrhage' => 'Kutoka damu nyingi baada ya kujifungua',
            'b_retained_placenta'      => 'Kondo la nyuma kukwama',
        ],
        'C' => [
            'c_bp_high'          => 'BP 140/90 au zaidi',
            'c_over_40_weeks'    => 'Umri wa mimba zaidi ya wiki 40',
            'c_hb_below_85'      => 'Hb chini ya 8.5 gm/dl',
            'c_reduced_movement' => 'Mtoto kuria tumboni / kupungua kucheza',
            'c_albumin_urine'    => 'Albumin kwenye mkojo',
            'c_bad_position'     => 'Mtoto amelala vibaya baada ya wiki 36',
            'c_sugar_urine'      => 'Sukari katika mkojo',
            'c_leg_oedema'       => 'Kuvimba miguu',
            'c_twins'            => 'Mama ana mapacha',
            'c_abnormal_fundal'  => 'Kimo cha mimba si sahihi kwa umri wa wiki',
        ],
    ];
}

/** Flat list of every risk column. */
function rch4_all_risk_columns()
{
    $cols = [];
    foreach (rch4_risk_fields() as $group) {
        foreach ($group as $col => $label) { $cols[] = $col; }
    }
    return $cols;
}

/** The rows of the Tab 3 attendance table: [column => label]. */
function rch4_visit_rows()
{
    return [
        'weight'           => 'Uzito (Kilo)',
        'blood_pressure'   => 'Blood Pressure mmHg (140/90)',
        'urine_albumin'    => 'Albumin Kwenye Mkojo (+)',
        'hb_level'         => 'Damu Hb (8.5 gm/dl)',
        'urine_sugar'      => 'Sukari Kwenye Mkojo',
        'gestational_age'  => 'Umri wa Mimba kwa Wiki',
        'fundal_height'    => 'Kimo cha Mimba kwa Wiki',
        'fetal_position'   => 'Mlalo wa Mtoto',
        'presentation'     => 'Kitangulizi (Kuanzia Wiki 36)',
        'fetal_movement'   => 'Mtoto Anacheza (Ndiyo/Hapana)',
        'fetal_heart_rate' => 'Mapigo ya Moyo wa Mtoto',
        'leg_oedema'       => 'Kuvimba Miguu (Oedema)',
        'ferrous_sulphate' => 'Ferrous Sulphate (2 kila siku)',
        'folic_acid'       => 'Folic Acid (1 kila siku)',
        'sp_dose'          => 'Malaria SP (Vidonge 3)',
        'mebendazole'      => 'Mebendazole (500mg)',
        'tt_vaccine'       => 'Chanjo ya Pepopunda (TT)',
    ];
}

/**
 * Load the full RCH4 data bundle for a patient.
 */
function rch4_load($patientId)
{
    $pdo = db();

    $p = $pdo->prepare('SELECT * FROM patients WHERE id = ?');
    $p->execute([$patientId]);
    $patient = $p->fetch();
    if (!$patient) return null;

    $c = $pdo->prepare('SELECT * FROM rch4_cards WHERE patient_id = ? ORDER BY id LIMIT 1');
    $c->execute([$patientId]);
    $card = $c->fetch() ?: [];

    $r = $pdo->prepare('SELECT * FROM risk_assessment WHERE patient_id = ? ORDER BY id DESC LIMIT 1');
    $r->execute([$patientId]);
    $risk = $r->fetch() ?: [];

    $l = $pdo->prepare('SELECT * FROM laboratory_results WHERE patient_id = ? ORDER BY id DESC LIMIT 1');
    $l->execute([$patientId]);
    $lab = $l->fetch() ?: [];

    // Visits indexed by visit number (1..4).
    $v = $pdo->prepare('SELECT * FROM anc_visits WHERE patient_id = ? ORDER BY visit_number');
    $v->execute([$patientId]);
    $visits = [];
    foreach ($v->fetchAll() as $row) { $visits[(int) $row['visit_number']] = $row; }

    return compact('patient', 'card', 'risk', 'lab', 'visits');
}

/**
 * Decide whether the pregnancy is high risk.
 * Rule (from the roadmap): if ANY Section C danger sign is ticked,
 * the pregnancy is automatically HIGH RISK.
 */
function rch4_is_high_risk(array $riskValues)
{
    foreach (rch4_risk_fields()['C'] as $col => $label) {
        if (!empty($riskValues[$col])) {
            return true;
        }
    }
    return false;
}
