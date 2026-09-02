<?php

include "connection.php";
include "auth.php";

if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit();
}

// Your Ayat of the Day code starts here...

// ── Ayat of the Day ───────────────────────────────────────────────────────

$ayaat = [
    ['arabic' => 'إِنَّ مَعَ الْعُسْرِ يُسْرًا', 'urdu' => 'بے شک تکلیف کے ساتھ آسانی ہے', 'english' => 'Indeed, with hardship comes ease.', 'ref' => 'Surah Al-Inshirah • 94:6'],
    ['arabic' => 'وَعَسَىٰ أَن تَكْرَهُوا شَيْئًا وَهُوَ خَيْرٌ لَّكُمْ', 'urdu' => 'شاید تم کسی چیز کو ناپسند کرو حالانکہ وہ تمہارے لیے بہتر ہو', 'english' => 'Perhaps you dislike a thing and it is good for you.', 'ref' => 'Surah Al-Baqarah • 2:216'],
    ['arabic' => 'فَإِنَّ مَعَ الْعُسْرِ يُسْرًا', 'urdu' => 'پس بے شک ہر مشکل کے ساتھ آسانی ہے', 'english' => 'For indeed, with every difficulty comes relief.', 'ref' => 'Surah Al-Inshirah • 94:5'],
    ['arabic' => 'وَلَا تَيْأَسُوا مِن رَّوْحِ اللَّهِ', 'urdu' => 'اللہ کی رحمت سے مایوس نہ ہو', 'english' => 'Do not despair of the mercy of Allah.', 'ref' => 'Surah Az-Zumar • 39:53'],
    ['arabic' => 'حَسْبُنَا اللَّهُ وَنِعْمَ الْوَكِيلُ', 'urdu' => 'ہمیں اللہ کافی ہے اور وہ بہترین کارساز ہے', 'english' => 'Allah is sufficient for us, and He is the best disposer of affairs.', 'ref' => 'Surah Aal-e-Imran • 3:173'],
    ['arabic' => 'وَهُوَ مَعَكُمْ أَيْنَ مَا كُنتُمْ', 'urdu' => 'اور وہ تمہارے ساتھ ہے جہاں بھی تم ہو', 'english' => 'And He is with you wherever you are.', 'ref' => 'Surah Al-Hadid • 57:4'],
    ['arabic' => 'إِنَّ اللَّهَ مَعَ الصَّابِرِينَ', 'urdu' => 'بے شک اللہ صبر کرنے والوں کے ساتھ ہے', 'english' => 'Indeed, Allah is with the patient.', 'ref' => 'Surah Al-Baqarah • 2:153'],
    ['arabic' => 'وَاللَّهُ يُحِبُّ الْمُحْسِنِينَ', 'urdu' => 'اور اللہ نیکی کرنے والوں سے محبت کرتا ہے', 'english' => 'And Allah loves the doers of good.', 'ref' => 'Surah Aal-e-Imran • 3:134'],
    ['arabic' => 'إِنَّ اللَّهَ يُحِبُّ التَّوَّابِينَ', 'urdu' => 'بے شک اللہ توبہ کرنے والوں سے محبت کرتا ہے', 'english' => 'Indeed, Allah loves those who repent.', 'ref' => 'Surah Al-Baqarah • 2:222'],
    ['arabic' => 'وَهُوَ الْغَفُورُ الرَّحِيمُ', 'urdu' => 'اور وہ بڑا بخشنے والا مہربان ہے', 'english' => 'And He is the Forgiving, the Merciful.', 'ref' => 'Surah Yunus • 10:107'],
    ['arabic' => 'فَاذْكُرُونِي أَذْكُرْكُمْ', 'urdu' => 'تم مجھے یاد کرو میں تمہیں یاد کروں گا', 'english' => 'Remember Me and I will remember you.', 'ref' => 'Surah Al-Baqarah • 2:152'],
    ['arabic' => 'وَإِذَا سَأَلَكَ عِبَادِي عَنِّي فَإِنِّي قَرِيبٌ', 'urdu' => 'جب میرے بندے میرے بارے میں پوچھیں تو میں قریب ہوں', 'english' => 'When My servants ask about Me, I am near.', 'ref' => 'Surah Al-Baqarah • 2:186'],
    ['arabic' => 'وَعَلَى اللَّهِ فَتَوَكَّلُوا', 'urdu' => 'اور اللہ پر بھروسہ رکھو', 'english' => 'And upon Allah rely.', 'ref' => 'Surah Al-Maidah • 5:23'],
    ['arabic' => 'إِنَّ اللَّهَ لَا يُضِيعُ أَجْرَ الْمُحْسِنِينَ', 'urdu' => 'بے شک اللہ نیکوکاروں کا اجر ضائع نہیں کرتا', 'english' => 'Indeed, Allah does not waste the reward of the good-doers.', 'ref' => 'Surah At-Tawbah • 9:120'],
    ['arabic' => 'وَمَن يَتَّقِ اللَّهَ يَجْعَل لَّهُ مَخْرَجًا', 'urdu' => 'جو اللہ سے ڈرے اللہ اس کے لیے راستہ نکال دیتا ہے', 'english' => 'Whoever fears Allah, He will make a way out for him.', 'ref' => 'Surah At-Talaq • 65:2'],
    ['arabic' => 'وَيَرْزُقْهُ مِنْ حَيْثُ لَا يَحْتَسِبُ', 'urdu' => 'اور اسے وہاں سے رزق دیتا ہے جہاں سے وہ گمان بھی نہ کرتا ہو', 'english' => 'And provides for him from where he does not expect.', 'ref' => 'Surah At-Talaq • 65:3'],
    ['arabic' => 'وَمَن يَتَوَكَّلْ عَلَى اللَّهِ فَهُوَ حَسْبُهُ', 'urdu' => 'جو اللہ پر توکل کرے تو وہ اسے کافی ہے', 'english' => 'Whoever relies upon Allah, then He is sufficient for him.', 'ref' => 'Surah At-Talaq • 65:3'],
    ['arabic' => 'إِنَّ اللَّهَ عَلَىٰ كُلِّ شَيْءٍ قَدِيرٌ', 'urdu' => 'بے شک اللہ ہر چیز پر قادر ہے', 'english' => 'Indeed, Allah is over all things competent.', 'ref' => 'Surah Al-Baqarah • 2:20'],
    ['arabic' => 'وَاللَّهُ خَيْرُ الرَّازِقِينَ', 'urdu' => 'اور اللہ سب سے بہتر رزق دینے والا ہے', 'english' => 'And Allah is the best of providers.', 'ref' => 'Surah Al-Jumu\'ah • 62:11'],
    ['arabic' => 'رَبِّ إِنِّي لِمَا أَنزَلْتَ إِلَيَّ مِنْ خَيْرٍ فَقِيرٌ', 'urdu' => 'اے رب جو بھی بھلائی تو مجھ پر نازل کرے میں اس کا محتاج ہوں', 'english' => 'My Lord, I am in need of whatever good You send down to me.', 'ref' => 'Surah Al-Qasas • 28:24'],
    ['arabic' => 'لَا يُكَلِّفُ اللَّهُ نَفْسًا إِلَّا وُسْعَهَا', 'urdu' => 'اللہ کسی نفس کو اس کی طاقت سے زیادہ تکلیف نہیں دیتا', 'english' => 'Allah does not burden a soul beyond that it can bear.', 'ref' => 'Surah Al-Baqarah • 2:286'],
    ['arabic' => 'وَنَحْنُ أَقْرَبُ إِلَيْهِ مِنْ حَبْلِ الْوَرِيدِ', 'urdu' => 'اور ہم اس کی شہ رگ سے بھی زیادہ قریب ہیں', 'english' => 'And We are closer to him than his jugular vein.', 'ref' => 'Surah Qaf • 50:16'],
    ['arabic' => 'قُلْ هُوَ اللَّهُ أَحَدٌ', 'urdu' => 'کہو وہ اللہ ایک ہے', 'english' => 'Say: He is Allah, the One.', 'ref' => 'Surah Al-Ikhlas • 112:1'],
    ['arabic' => 'اللَّهُ لَا إِلَٰهَ إِلَّا هُوَ الْحَيُّ الْقَيُّومُ', 'urdu' => 'اللہ وہ ہے جس کے سوا کوئی معبود نہیں زندہ اور قائم رہنے والا', 'english' => 'Allah, there is no deity except Him, the Ever-Living, the Sustainer.', 'ref' => 'Surah Al-Baqarah • 2:255'],
    ['arabic' => 'وَقُل رَّبِّ زِدْنِي عِلْمًا', 'urdu' => 'اور کہو اے رب مجھے علم میں اضافہ دے', 'english' => 'And say: My Lord, increase me in knowledge.', 'ref' => 'Surah Ta-Ha • 20:114'],
    ['arabic' => 'رَبَّنَا آتِنَا فِي الدُّنْيَا حَسَنَةً وَفِي الْآخِرَةِ حَسَنَةً', 'urdu' => 'اے ہمارے رب ہمیں دنیا میں بھلائی دے اور آخرت میں بھی بھلائی دے', 'english' => 'Our Lord, give us good in this world and good in the hereafter.', 'ref' => 'Surah Al-Baqarah • 2:201'],
    ['arabic' => 'إِنَّ اللَّهَ مَعَ الَّذِينَ اتَّقَوا', 'urdu' => 'بے شک اللہ ان لوگوں کے ساتھ ہے جو تقوی اختیار کرتے ہیں', 'english' => 'Indeed, Allah is with those who fear Him.', 'ref' => 'Surah An-Nahl • 16:128'],
    ['arabic' => 'وَاصْبِرْ إِنَّ اللَّهَ لَا يُضِيعُ أَجْرَ الْمُحْسِنِينَ', 'urdu' => 'صبر کرو بے شک اللہ نیکوکاروں کا اجر ضائع نہیں کرتا', 'english' => 'Be patient; indeed, Allah does not waste the reward of the good-doers.', 'ref' => 'Surah Hud • 11:115'],
    ['arabic' => 'إِنَّ اللَّهَ يُحِبُّ الْمُتَوَكِّلِينَ', 'urdu' => 'بے شک اللہ توکل کرنے والوں سے محبت کرتا ہے', 'english' => 'Indeed, Allah loves those who rely upon Him.', 'ref' => 'Surah Aal-e-Imran • 3:159'],
    ['arabic' => 'وَاللَّهُ وَلِيُّ الْمُؤْمِنِينَ', 'urdu' => 'اور اللہ مومنوں کا ولی ہے', 'english' => 'And Allah is the guardian of the believers.', 'ref' => 'Surah Aal-e-Imran • 3:68'],
    ['arabic' => 'وَلَا تَقْنَطُوا مِن رَّحْمَةِ اللَّهِ', 'urdu' => 'اور اللہ کی رحمت سے مایوس نہ ہو', 'english' => 'And do not despair of the mercy of Allah.', 'ref' => 'Surah Az-Zumar • 39:53'],
    ['arabic' => 'إِنَّ رَحْمَتَ اللَّهِ قَرِيبٌ مِّنَ الْمُحْسِنِينَ', 'urdu' => 'بے شک اللہ کی رحمت نیکوکاروں کے قریب ہے', 'english' => 'Indeed, the mercy of Allah is near to the doers of good.', 'ref' => 'Surah Al-A\'raf • 7:56'],
    ['arabic' => 'وَاللَّهُ غَفُورٌ رَّحِيمٌ', 'urdu' => 'اور اللہ بخشنے والا مہربان ہے', 'english' => 'And Allah is Forgiving and Merciful.', 'ref' => 'Surah Al-Baqarah • 2:173'],
    ['arabic' => 'تُبْ إِلَى اللَّهِ جَمِيعًا أَيُّهَ الْمُؤْمِنُونَ', 'urdu' => 'اے مومنو سب مل کر اللہ کی طرف توبہ کرو', 'english' => 'Turn to Allah in repentance altogether, O believers.', 'ref' => 'Surah An-Nur • 24:31'],
    ['arabic' => 'وَهُوَ أَرْحَمُ الرَّاحِمِينَ', 'urdu' => 'اور وہ سب سے زیادہ رحم کرنے والا ہے', 'english' => 'And He is the most merciful of the merciful.', 'ref' => 'Surah Yusuf • 12:64'],
    ['arabic' => 'إِنَّ اللَّهَ لَطِيفٌ بِعِبَادِهِ', 'urdu' => 'بے شک اللہ اپنے بندوں پر مہربان ہے', 'english' => 'Indeed, Allah is subtle with His servants.', 'ref' => 'Surah Ash-Shura • 42:19'],
    ['arabic' => 'وَهُوَ الْعَلِيمُ الْحَكِيمُ', 'urdu' => 'اور وہ علم والا اور حکمت والا ہے', 'english' => 'And He is the Knowing, the Wise.', 'ref' => 'Surah At-Tahrim • 66:2'],
    ['arabic' => 'سُبْحَانَ اللَّهِ وَبِحَمْدِهِ', 'urdu' => 'اللہ پاک ہے اور اسی کی تعریف ہے', 'english' => 'Glory be to Allah and praise be to Him.', 'ref' => 'Surah Al-Isra • 17:43'],
    ['arabic' => 'وَلَذِكْرُ اللَّهِ أَكْبَرُ', 'urdu' => 'اور اللہ کا ذکر سب سے بڑا ہے', 'english' => 'And the remembrance of Allah is greater.', 'ref' => 'Surah Al-Ankabut • 29:45'],
    ['arabic' => 'أَلَا بِذِكْرِ اللَّهِ تَطْمَئِنُّ الْقُلُوبُ', 'urdu' => 'خبردار اللہ کے ذکر سے ہی دلوں کو اطمینان ملتا ہے', 'english' => 'Verily, in the remembrance of Allah do hearts find rest.', 'ref' => 'Surah Ar-Ra\'d • 13:28'],
    ['arabic' => 'وَمَا تَوْفِيقِي إِلَّا بِاللَّهِ', 'urdu' => 'اور میری توفیق صرف اللہ کی طرف سے ہے', 'english' => 'And my success is not but through Allah.', 'ref' => 'Surah Hud • 11:88'],
    ['arabic' => 'رَبَّنَا لَا تُزِغْ قُلُوبَنَا بَعْدَ إِذْ هَدَيْتَنَا', 'urdu' => 'اے ہمارے رب ہدایت دینے کے بعد ہمارے دلوں کو ٹیڑھا نہ کر', 'english' => 'Our Lord, do not let our hearts deviate after You have guided us.', 'ref' => 'Surah Aal-e-Imran • 3:8'],
    ['arabic' => 'رَبِّ اشْرَحْ لِي صَدْرِي', 'urdu' => 'اے میرے رب میرا سینہ کھول دے', 'english' => 'My Lord, expand for me my breast.', 'ref' => 'Surah Ta-Ha • 20:25'],
    ['arabic' => 'وَيَسِّرْ لِي أَمْرِي', 'urdu' => 'اور میرے کام کو آسان کر دے', 'english' => 'And ease for me my task.', 'ref' => 'Surah Ta-Ha • 20:26'],
    ['arabic' => 'رَبَّنَا اغْفِرْ لَنَا ذُنُوبَنَا', 'urdu' => 'اے ہمارے رب ہمارے گناہ بخش دے', 'english' => 'Our Lord, forgive us our sins.', 'ref' => 'Surah Aal-e-Imran • 3:193'],
    ['arabic' => 'إِنَّكَ أَنتَ الْعَزِيزُ الْحَكِيمُ', 'urdu' => 'بے شک تو ہی غالب اور حکمت والا ہے', 'english' => 'Indeed, You are the Exalted in Might, the Wise.', 'ref' => 'Surah Al-Baqarah • 2:129'],
    ['arabic' => 'إِنَّ اللَّهَ سَمِيعٌ عَلِيمٌ', 'urdu' => 'بے شک اللہ سننے والا جاننے والا ہے', 'english' => 'Indeed, Allah is Hearing and Knowing.', 'ref' => 'Surah Al-Baqarah • 2:227'],
    ['arabic' => 'إِنَّ اللَّهَ لَا يَظْلِمُ النَّاسَ شَيْئًا', 'urdu' => 'بے شک اللہ لوگوں پر ذرا بھی ظلم نہیں کرتا', 'english' => 'Indeed, Allah does not wrong people at all.', 'ref' => 'Surah Yunus • 10:44'],
    ['arabic' => 'وَاللَّهُ يَعْلَمُ مَا تُسِرُّونَ وَمَا تُعْلِنُونَ', 'urdu' => 'اور اللہ جانتا ہے جو تم چھپاتے ہو اور جو ظاہر کرتے ہو', 'english' => 'And Allah knows what you conceal and what you declare.', 'ref' => 'Surah An-Nahl • 16:19'],
    ['arabic' => 'وَهُوَ بِكُلِّ شَيْءٍ عَلِيمٌ', 'urdu' => 'اور وہ ہر چیز کو جاننے والا ہے', 'english' => 'And He is Knowing of all things.', 'ref' => 'Surah Al-Baqarah • 2:29'],
    ['arabic' => 'وَاللَّهُ بَصِيرٌ بِمَا تَعْمَلُونَ', 'urdu' => 'اور اللہ تمہارے اعمال دیکھ رہا ہے', 'english' => 'And Allah is Seeing of what you do.', 'ref' => 'Surah Al-Hujurat • 49:18'],
    ['arabic' => 'فَإِنَّ اللَّهَ يَعْلَمُ سِرَّكُمْ وَجَهْرَكُمْ', 'urdu' => 'بے شک اللہ تمہارے راز اور اعلانیہ کو جانتا ہے', 'english' => 'Indeed, Allah knows your secrets and what you make public.', 'ref' => 'Surah Ya-Sin • 36:76'],
    ['arabic' => 'وَهُوَ الشَّكُورُ الْحَلِيمُ', 'urdu' => 'اور وہ قدردان اور بردبار ہے', 'english' => 'And He is the Appreciative, the Forbearing.', 'ref' => 'Surah Fatir • 35:30'],
    ['arabic' => 'اشْكُرُوا لِي وَلَا تَكْفُرُونِ', 'urdu' => 'میرا شکر ادا کرو اور ناشکری نہ کرو', 'english' => 'Be grateful to Me and do not deny Me.', 'ref' => 'Surah Al-Baqarah • 2:152'],
    ['arabic' => 'لَئِن شَكَرْتُمْ لَأَزِيدَنَّكُمْ', 'urdu' => 'اگر تم شکر کرو گے تو میں تمہیں اور زیادہ دوں گا', 'english' => 'If you are grateful, I will surely increase you.', 'ref' => 'Surah Ibrahim • 14:7'],
    ['arabic' => 'وَإِن تَعُدُّوا نِعْمَةَ اللَّهِ لَا تُحْصُوهَا', 'urdu' => 'اور اگر اللہ کی نعمتیں گنو تو شمار نہ کر سکو', 'english' => 'And if you count the favors of Allah, you could not enumerate them.', 'ref' => 'Surah An-Nahl • 16:18'],
    ['arabic' => 'وَمَا بِكُم مِّن نِّعْمَةٍ فَمِنَ اللَّهِ', 'urdu' => 'اور تمہارے پاس جو بھی نعمت ہے وہ اللہ کی طرف سے ہے', 'english' => 'And whatever you have of favor, it is from Allah.', 'ref' => 'Surah An-Nahl • 16:53'],
    ['arabic' => 'إِنَّ الْإِنسَانَ لَفِي خُسْرٍ', 'urdu' => 'بے شک انسان گھاٹے میں ہے', 'english' => 'Indeed, mankind is in loss.', 'ref' => 'Surah Al-Asr • 103:2'],
    ['arabic' => 'إِنَّ مَعَ الْيُسْرِ عُسْرًا', 'urdu' => 'بے شک آسانی کے ساتھ تکلیف بھی ہے', 'english' => 'Indeed, with ease there is difficulty.', 'ref' => 'Surah Ash-Sharh • 94:6'],
    ['arabic' => 'وَأَنَّ إِلَىٰ رَبِّكَ الْمُنتَهَىٰ', 'urdu' => 'اور یہ کہ تیرے رب ہی کی طرف انتہا ہے', 'english' => 'And that to your Lord is the finality.', 'ref' => 'Surah An-Najm • 53:42'],
    ['arabic' => 'كُلُّ نَفْسٍ ذَائِقَةُ الْمَوْتِ', 'urdu' => 'ہر نفس کو موت کا ذائقہ چکھنا ہے', 'english' => 'Every soul will taste death.', 'ref' => 'Surah Aal-e-Imran • 3:185'],
    ['arabic' => 'وَإِلَى اللَّهِ تُرْجَعُ الْأُمُورُ', 'urdu' => 'اور تمام معاملات اللہ کی طرف لوٹتے ہیں', 'english' => 'And to Allah all matters are returned.', 'ref' => 'Surah Al-Baqarah • 2:210'],
    ['arabic' => 'إِنَّا لِلَّهِ وَإِنَّا إِلَيْهِ رَاجِعُونَ', 'urdu' => 'بے شک ہم اللہ کے لیے ہیں اور اسی کی طرف لوٹنے والے ہیں', 'english' => 'Indeed, to Allah we belong and to Him we shall return.', 'ref' => 'Surah Al-Baqarah • 2:156'],
    ['arabic' => 'وَمَا الْحَيَاةُ الدُّنْيَا إِلَّا مَتَاعُ الْغُرُورِ', 'urdu' => 'اور دنیا کی زندگی صرف دھوکے کا سامان ہے', 'english' => 'And the life of this world is only the enjoyment of delusion.', 'ref' => 'Surah Aal-e-Imran • 3:185'],
    ['arabic' => 'وَالْآخِرَةُ خَيْرٌ وَأَبْقَىٰ', 'urdu' => 'اور آخرت بہتر اور باقی رہنے والی ہے', 'english' => 'And the hereafter is better and more enduring.', 'ref' => 'Surah Al-A\'la • 87:17'],
    ['arabic' => 'يَا أَيُّهَا الَّذِينَ آمَنُوا اتَّقُوا اللَّهَ', 'urdu' => 'اے ایمان والو اللہ سے ڈرو', 'english' => 'O you who believe, fear Allah.', 'ref' => 'Surah Al-Hashr • 59:18'],
    ['arabic' => 'وَاتَّقُوا اللَّهَ وَيُعَلِّمُكُمُ اللَّهُ', 'urdu' => 'اللہ سے ڈرو اور اللہ تمہیں سکھاتا ہے', 'english' => 'Fear Allah and Allah teaches you.', 'ref' => 'Surah Al-Baqarah • 2:282'],
    ['arabic' => 'وَاتَّقُوا يَوْمًا تُرْجَعُونَ فِيهِ إِلَى اللَّهِ', 'urdu' => 'اس دن سے ڈرو جب تم اللہ کی طرف لوٹائے جاؤ گے', 'english' => 'Fear a day when you will be returned to Allah.', 'ref' => 'Surah Al-Baqarah • 2:281'],
    ['arabic' => 'إِنَّ اللَّهَ يُحِبُّ الْمُقْسِطِينَ', 'urdu' => 'بے شک اللہ انصاف کرنے والوں سے محبت کرتا ہے', 'english' => 'Indeed, Allah loves those who act justly.', 'ref' => 'Surah Al-Maidah • 5:42'],
    ['arabic' => 'وَأَقِيمُوا الصَّلَاةَ وَآتُوا الزَّكَاةَ', 'urdu' => 'نماز قائم کرو اور زکوٰۃ ادا کرو', 'english' => 'Establish prayer and give zakah.', 'ref' => 'Surah Al-Baqarah • 2:43'],
    ['arabic' => 'إِنَّ الصَّلَاةَ تَنْهَىٰ عَنِ الْفَحْشَاءِ وَالْمُنكَرِ', 'urdu' => 'بے شک نماز بے حیائی اور برائی سے روکتی ہے', 'english' => 'Indeed, prayer prohibits immorality and wrongdoing.', 'ref' => 'Surah Al-Ankabut • 29:45'],
    ['arabic' => 'وَاسْتَعِينُوا بِالصَّبْرِ وَالصَّلَاةِ', 'urdu' => 'صبر اور نماز سے مدد لو', 'english' => 'Seek help through patience and prayer.', 'ref' => 'Surah Al-Baqarah • 2:45'],
    ['arabic' => 'إِنَّ اللَّهَ يُحِبُّ الَّذِينَ يُقَاتِلُونَ فِي سَبِيلِهِ صَفًّا', 'urdu' => 'بے شک اللہ ان لوگوں سے محبت کرتا ہے جو اس کی راہ میں صف باندھ کر لڑتے ہیں', 'english' => 'Indeed, Allah loves those who fight in His cause in a row.', 'ref' => 'Surah As-Saf • 61:4'],
    ['arabic' => 'وَأَطِيعُوا اللَّهَ وَرَسُولَهُ', 'urdu' => 'اور اللہ اور اس کے رسول کی اطاعت کرو', 'english' => 'And obey Allah and His messenger.', 'ref' => 'Surah Al-Anfal • 8:46'],
    ['arabic' => 'وَمَن يُطِعِ اللَّهَ وَرَسُولَهُ فَقَدْ فَازَ فَوْزًا عَظِيمًا', 'urdu' => 'جو اللہ اور اس کے رسول کی اطاعت کرے وہ بڑی کامیابی حاصل کرتا ہے', 'english' => 'Whoever obeys Allah and His messenger has attained a great triumph.', 'ref' => 'Surah Al-Ahzab • 33:71'],
    ['arabic' => 'وَمَا يَنطِقُ عَنِ الْهَوَىٰ', 'urdu' => 'اور وہ اپنی خواہش سے نہیں بولتے', 'english' => 'Nor does he speak from his own desire.', 'ref' => 'Surah An-Najm • 53:3'],
    ['arabic' => 'لَقَدْ كَانَ لَكُمْ فِي رَسُولِ اللَّهِ أُسْوَةٌ حَسَنَةٌ', 'urdu' => 'یقیناً تمہارے لیے رسول اللہ میں بہترین نمونہ ہے', 'english' => 'Indeed, in the messenger of Allah you have an excellent example.', 'ref' => 'Surah Al-Ahzab • 33:21'],
    ['arabic' => 'وَمَا أَرْسَلْنَاكَ إِلَّا رَحْمَةً لِّلْعَالَمِينَ', 'urdu' => 'اور ہم نے آپ کو تمام جہانوں کے لیے رحمت بنا کر بھیجا', 'english' => 'And We have not sent you except as a mercy to the worlds.', 'ref' => 'Surah Al-Anbiya • 21:107'],
    ['arabic' => 'يَا أَيُّهَا النَّاسُ اتَّقُوا رَبَّكُمْ', 'urdu' => 'اے لوگو اپنے رب سے ڈرو', 'english' => 'O mankind, fear your Lord.', 'ref' => 'Surah An-Nisa • 4:1'],
    ['arabic' => 'خُذِ الْعَفْوَ وَأْمُرْ بِالْعُرْفِ', 'urdu' => 'معافی اختیار کرو اور بھلائی کا حکم دو', 'english' => 'Take what is given freely and enjoin what is good.', 'ref' => 'Surah Al-A\'raf • 7:199'],
    ['arabic' => 'وَأَحْسِنُوا إِنَّ اللَّهَ يُحِبُّ الْمُحْسِنِينَ', 'urdu' => 'اور احسان کرو بے شک اللہ احسان کرنے والوں سے محبت کرتا ہے', 'english' => 'And do good; indeed, Allah loves the doers of good.', 'ref' => 'Surah Al-Baqarah • 2:195'],
    ['arabic' => 'وَلَا تُفْسِدُوا فِي الْأَرْضِ بَعْدَ إِصْلَاحِهَا', 'urdu' => 'اور زمین میں اصلاح کے بعد فساد نہ پھیلاؤ', 'english' => 'And do not cause corruption on earth after its reformation.', 'ref' => 'Surah Al-A\'raf • 7:56'],
    ['arabic' => 'وَلَا تَجْعَلْ يَدَكَ مَغْلُولَةً إِلَىٰ عُنُقِكَ', 'urdu' => 'اور اپنا ہاتھ گردن سے باندھے نہ رکھو', 'english' => 'And do not make your hand tied to your neck.', 'ref' => 'Surah Al-Isra • 17:29'],
    ['arabic' => 'وَلَا تَبْذِرْ تَبْذِيرًا', 'urdu' => 'اور فضول خرچی نہ کرو', 'english' => 'And do not spend wastefully.', 'ref' => 'Surah Al-Isra • 17:26'],
    ['arabic' => 'إِنَّ الْمُبَذِّرِينَ كَانُوا إِخْوَانَ الشَّيَاطِينِ', 'urdu' => 'بے شک فضول خرچ لوگ شیطان کے بھائی ہیں', 'english' => 'Indeed, the wasteful are brothers of the devils.', 'ref' => 'Surah Al-Isra • 17:27'],
    ['arabic' => 'وَلَا تَقْرَبُوا الزِّنَا إِنَّهُ كَانَ فَاحِشَةً', 'urdu' => 'اور زنا کے قریب نہ جاؤ بے شک یہ بے حیائی ہے', 'english' => 'And do not approach unlawful sexual intercourse; it is an immorality.', 'ref' => 'Surah Al-Isra • 17:32'],
    ['arabic' => 'وَلَا تَقْتُلُوا النَّفْسَ الَّتِي حَرَّمَ اللَّهُ', 'urdu' => 'اور اس جان کو قتل نہ کرو جسے اللہ نے حرام کیا ہے', 'english' => 'And do not kill the soul which Allah has forbidden.', 'ref' => 'Surah Al-Isra • 17:33'],
    ['arabic' => 'وَبِالْوَالِدَيْنِ إِحْسَانًا', 'urdu' => 'اور والدین کے ساتھ اچھا سلوک کرو', 'english' => 'And to parents, do good.', 'ref' => 'Surah Al-Baqarah • 2:83'],
    ['arabic' => 'وَقُل لَّهُمَا قَوْلًا كَرِيمًا', 'urdu' => 'اور ان سے عزت کے ساتھ بات کرو', 'english' => 'And speak to them words of appropriate kindness.', 'ref' => 'Surah Al-Isra • 17:23'],
    ['arabic' => 'وَاخْفِضْ لَهُمَا جَنَاحَ الذُّلِّ مِنَ الرَّحْمَةِ', 'urdu' => 'اور رحمت سے ان کے آگے انکساری کا بازو جھکاؤ', 'english' => 'And lower to them the wing of humility out of mercy.', 'ref' => 'Surah Al-Isra • 17:24'],
    ['arabic' => 'وَأَوْفُوا بِالْعَهْدِ إِنَّ الْعَهْدَ كَانَ مَسْئُولًا', 'urdu' => 'اور وعدہ پورا کرو بے شک وعدے کے بارے میں پوچھا جائے گا', 'english' => 'And fulfill every commitment; indeed, commitments will be questioned.', 'ref' => 'Surah Al-Isra • 17:34'],
    ['arabic' => 'يَا أَيُّهَا الَّذِينَ آمَنُوا لَا تَخُونُوا اللَّهَ وَالرَّسُولَ', 'urdu' => 'اے ایمان والو اللہ اور رسول سے خیانت نہ کرو', 'english' => 'O you who believe, do not betray Allah and the messenger.', 'ref' => 'Surah Al-Anfal • 8:27'],
    ['arabic' => 'وَلَا تَجَسَّسُوا وَلَا يَغْتَب بَّعْضُكُم بَعْضًا', 'urdu' => 'اور جاسوسی نہ کرو اور ایک دوسرے کی غیبت نہ کرو', 'english' => 'And do not spy or backbite each other.', 'ref' => 'Surah Al-Hujurat • 49:12'],
    ['arabic' => 'يَا أَيُّهَا الَّذِينَ آمَنُوا اجْتَنِبُوا كَثِيرًا مِّنَ الظَّنِّ', 'urdu' => 'اے ایمان والو بہت سے گمانوں سے بچو', 'english' => 'O believers, avoid much suspicion.', 'ref' => 'Surah Al-Hujurat • 49:12'],
    ['arabic' => 'إِنَّ أَكْرَمَكُمْ عِندَ اللَّهِ أَتْقَاكُمْ', 'urdu' => 'بے شک اللہ کے نزدیک تم میں سب سے زیادہ عزت والا وہ ہے جو سب سے زیادہ پرہیزگار ہے', 'english' => 'Indeed, the most noble of you in the sight of Allah is the most righteous.', 'ref' => 'Surah Al-Hujurat • 49:13'],
    ['arabic' => 'يَا أَيُّهَا النَّاسُ إِنَّا خَلَقْنَاكُم مِّن ذَكَرٍ وَأُنثَىٰ', 'urdu' => 'اے لوگو ہم نے تمہیں ایک مرد اور عورت سے پیدا کیا', 'english' => 'O mankind, We created you from a male and female.', 'ref' => 'Surah Al-Hujurat • 49:13'],
    ['arabic' => 'وَجَعَلْنَاكُمْ شُعُوبًا وَقَبَائِلَ لِتَعَارَفُوا', 'urdu' => 'اور تمہیں قوموں اور قبیلوں میں بنایا تاکہ تم ایک دوسرے کو پہچانو', 'english' => 'And made you peoples and tribes that you may know one another.', 'ref' => 'Surah Al-Hujurat • 49:13'],
    ['arabic' => 'وَقُولُوا لِلنَّاسِ حُسْنًا', 'urdu' => 'اور لوگوں سے اچھی بات کرو', 'english' => 'And speak to people good words.', 'ref' => 'Surah Al-Baqarah • 2:83'],
    ['arabic' => 'ادْفَعْ بِالَّتِي هِيَ أَحْسَنُ', 'urdu' => 'برائی کو بھلائی سے دور کرو', 'english' => 'Repel evil with that which is better.', 'ref' => 'Surah Al-Mu\'minun • 23:96'],
    ['arabic' => 'وَلَا تَسْتَوِي الْحَسَنَةُ وَلَا السَّيِّئَةُ', 'urdu' => 'اور نیکی اور بدی برابر نہیں ہو سکتی', 'english' => 'And good and evil are not equal.', 'ref' => 'Surah Fussilat • 41:34'],
    ['arabic' => 'فَاصْبِرْ صَبْرًا جَمِيلًا', 'urdu' => 'تو خوبصورتی سے صبر کرو', 'english' => 'So be patient with gracious patience.', 'ref' => 'Surah Al-Ma\'arij • 70:5'],
    ['arabic' => 'وَبَشِّرِ الصَّابِرِينَ', 'urdu' => 'اور صبر کرنے والوں کو خوشخبری دو', 'english' => 'And give good tidings to the patient.', 'ref' => 'Surah Al-Baqarah • 2:155'],
    ['arabic' => 'الَّذِينَ إِذَا أَصَابَتْهُم مُّصِيبَةٌ قَالُوا إِنَّا لِلَّهِ', 'urdu' => 'جب انہیں کوئی مصیبت پہنچے تو کہتے ہیں بے شک ہم اللہ کے لیے ہیں', 'english' => 'Those who, when disaster strikes, say: Indeed, to Allah we belong.', 'ref' => 'Surah Al-Baqarah • 2:156'],
    ['arabic' => 'وَلَنَبْلُوَنَّكُم بِشَيْءٍ مِّنَ الْخَوْفِ وَالْجُوعِ', 'urdu' => 'اور ہم تمہیں کچھ خوف اور بھوک سے آزمائیں گے', 'english' => 'And We will surely test you with something of fear and hunger.', 'ref' => 'Surah Al-Baqarah • 2:155'],
    ['arabic' => 'أَمْ حَسِبْتُمْ أَن تَدْخُلُوا الْجَنَّةَ', 'urdu' => 'کیا تم نے سوچا کہ جنت میں داخل ہو جاؤ گے', 'english' => 'Or do you think that you will enter paradise?', 'ref' => 'Surah Al-Baqarah • 2:214'],
    ['arabic' => 'إِنَّ الْجَنَّةَ لَهِيَ الْمَأْوَىٰ', 'urdu' => 'بے شک جنت ہی ٹھکانہ ہے', 'english' => 'Indeed, paradise is the refuge.', 'ref' => 'Surah An-Nazi\'at • 79:41'],
    ['arabic' => 'وَفِي ذَٰلِكَ فَلْيَتَنَافَسِ الْمُتَنَافِسُونَ', 'urdu' => 'اور اس میں مقابلہ کرنے والوں کو مقابلہ کرنا چاہیے', 'english' => 'For this let the competitors compete.', 'ref' => 'Surah Al-Mutaffifin • 83:26'],
];
$ayat = $ayaat[array_rand($ayaat)];


// ── STAT 1: Active Dars Areas ─────────────────────────────────────────────
$r = mysqli_query($conn, "SELECT COUNT(*) FROM dars_areas");
$totalAreas = (int) mysqli_fetch_row($r)[0];

$r = mysqli_query($conn, "SELECT COUNT(*) FROM dars_areas WHERE DATE_FORMAT(created_at,'%Y-%m') = DATE_FORMAT(NOW(),'%Y-%m')");
$newAreasThisMonth = (int) mysqli_fetch_row($r)[0];

// ── STAT 2: Total Users (everyone in DB) ──────────────────────────────────
$r = mysqli_query($conn, "SELECT COUNT(*) FROM users");
$totalUsers = (int) mysqli_fetch_row($r)[0];

$r = mysqli_query($conn, "SELECT COUNT(*) FROM users WHERE DATE_FORMAT(date_of_joining,'%Y-%m') = DATE_FORMAT(NOW(),'%Y-%m')");
$newUsersThisMonth = (int) mysqli_fetch_row($r)[0];

// ── STAT 3: Committee Members ─────────────────────────────────────────────
$r = mysqli_query($conn, "SELECT COUNT(*) FROM users WHERE role = 'committee'");
$totalCommittee = (int) mysqli_fetch_row($r)[0];

// ── STAT 4: Workshops This Month ──────────────────────────────────────────
$r = mysqli_query($conn, "
    SELECT COUNT(*) FROM events
    WHERE type = 'Workshop'
      AND YEAR(dateTime)  = YEAR(CURDATE())
      AND MONTH(dateTime) = MONTH(CURDATE())
");
$workshopsThisMonth = (int) mysqli_fetch_row($r)[0];

// ── Quran Attendance: members with 4+ days Present in previous week ────────
$r = mysqli_query($conn, "
    SELECT COUNT(DISTINCT user_id) 
    FROM (
        SELECT user_id, COUNT(*) AS days_present
        FROM quran_attendance
        WHERE status = 'Present'
          AND attendance_date >= DATE_SUB(CURDATE(), INTERVAL DAYOFWEEK(CURDATE())+6 DAY)
          AND attendance_date <  DATE_SUB(CURDATE(), INTERVAL DAYOFWEEK(CURDATE())-1 DAY)
        GROUP BY user_id
        HAVING days_present >= 4
    ) AS consistent_readers
");
$quranActiveLastWeek = (int) mysqli_fetch_row($r)[0];

// Total who had any quran attendance last week (for % calculation)
$r = mysqli_query($conn, "
    SELECT COUNT(DISTINCT user_id)
    FROM quran_attendance
    WHERE attendance_date >= DATE_SUB(CURDATE(), INTERVAL DAYOFWEEK(CURDATE())+6 DAY)
      AND attendance_date <  DATE_SUB(CURDATE(), INTERVAL DAYOFWEEK(CURDATE())-1 DAY)
");
$quranTotalLastWeek = (int) mysqli_fetch_row($r)[0];
$quranConsistencyPct = $quranTotalLastWeek > 0 
    ? round(($quranActiveLastWeek / $quranTotalLastWeek) * 100) 
    : 0;

// Quran trend: last 8 weeks — count of consistent readers (4+ days) per week
$r = mysqli_query($conn, "
    SELECT week_start, COUNT(*) AS consistent_count
    FROM (
        SELECT 
            DATE_SUB(attendance_date, INTERVAL DAYOFWEEK(attendance_date)-2 DAY) AS week_start,
            user_id,
            COUNT(*) AS days_present
        FROM quran_attendance
        WHERE status = 'Present'
          AND attendance_date >= DATE_SUB(CURDATE(), INTERVAL 8 WEEK)
        GROUP BY week_start, user_id
        HAVING days_present >= 4
    ) AS weekly
    GROUP BY week_start
    ORDER BY week_start ASC
    LIMIT 8
");
$quranTrend = [];
while ($row = mysqli_fetch_assoc($r)) {
    $quranTrend[] = (int)$row['consistent_count'];
}
while (count($quranTrend) < 8) array_unshift($quranTrend, 0);
$quranTrendJson = json_encode($quranTrend);

// Previous week vs week before that (for % change label)
$quranPrevWeek     = $quranTrend[count($quranTrend)-1] ?? 0;
$quranWeekBefore   = $quranTrend[count($quranTrend)-2] ?? 0;
$quranWeekChangePct = $quranWeekBefore > 0 
    ? round((($quranPrevWeek - $quranWeekBefore) / $quranWeekBefore) * 100) 
    : 0;

// ── Dars Attendance: total Present users last week (all areas) ─────────────
$r = mysqli_query($conn, "
    SELECT COUNT(*) 
    FROM dars_attendance
    WHERE attendance = 'Present'
      AND DATE(dateTime) >= DATE_SUB(CURDATE(), INTERVAL DAYOFWEEK(CURDATE())+6 DAY)
      AND DATE(dateTime) <  DATE_SUB(CURDATE(), INTERVAL DAYOFWEEK(CURDATE())-1 DAY)
");
$darsPresentLastWeek = (int) mysqli_fetch_row($r)[0];

// Total dars attendance records last week
$r = mysqli_query($conn, "
    SELECT COUNT(*)
    FROM dars_attendance
    WHERE DATE(dateTime) >= DATE_SUB(CURDATE(), INTERVAL DAYOFWEEK(CURDATE())+6 DAY)
      AND DATE(dateTime) <  DATE_SUB(CURDATE(), INTERVAL DAYOFWEEK(CURDATE())-1 DAY)
");
$darsTotalLastWeek  = (int) mysqli_fetch_row($r)[0];
$darsAttendancePct  = $darsTotalLastWeek > 0 
    ? round(($darsPresentLastWeek / $darsTotalLastWeek) * 100) 
    : 0;

// Dars trend: last 8 weeks — count of Present per week
$r = mysqli_query($conn, "
    SELECT 
        DATE_SUB(DATE(dateTime), INTERVAL DAYOFWEEK(DATE(dateTime))-2 DAY) AS week_start,
        COUNT(*) AS present_count
    FROM dars_attendance
    WHERE attendance = 'Present'
      AND DATE(dateTime) >= DATE_SUB(CURDATE(), INTERVAL 8 WEEK)
    GROUP BY week_start
    ORDER BY week_start ASC
    LIMIT 8
");
$darsTrend = [];
while ($row = mysqli_fetch_assoc($r)) {
    $darsTrend[] = (int)$row['present_count'];
}
while (count($darsTrend) < 8) array_unshift($darsTrend, 0);
$darsTrendJson = json_encode($darsTrend);

$darsPrevWeek   = $darsTrend[count($darsTrend)-1] ?? 0;
$darsWeekBefore = $darsTrend[count($darsTrend)-2] ?? 0;
$darsWeekChangePct = $darsWeekBefore > 0 
    ? round((($darsPrevWeek - $darsWeekBefore) / $darsWeekBefore) * 100) 
    : 0;


// ── All DB queries done — now safe to include layout files ─────────────────

include "header.php";
?>

<body>
  <div class="left-sidebar-pro">
    <?php include "sidebar.php"; ?>
  </div>
  <?php include "mainTopBar.php"; ?>

  <link href="https://fonts.googleapis.com/css2?family=Amiri:ital@0;1&family=Noto+Nastaliq+Urdu&display=swap" rel="stylesheet">

  <style>
    .ayat-banner {
      position: relative;
      overflow: hidden;
      background: linear-gradient(135deg, #0d1a2e 0%, #1b2a47 55%, #0d2040 100%);
      border-bottom: 1px solid rgba(255, 255, 255, .07);
      padding: 28px 32px 24px;
    }

    .ayat-banner::before {
      content: '';
      position: absolute;
      inset: 0;
      pointer-events: none;
      background: radial-gradient(ellipse at 80% 50%, rgba(8, 145, 178, .13) 0%, transparent 55%),
        radial-gradient(ellipse at 10% 80%, rgba(13, 148, 136, .09) 0%, transparent 50%);
    }

    .ayat-banner::after {
      content: '';
      position: absolute;
      inset: 0;
      pointer-events: none;
      background-image:
        repeating-linear-gradient(0deg, transparent, transparent 39px, rgba(255, 255, 255, .02) 40px),
        repeating-linear-gradient(90deg, transparent, transparent 39px, rgba(255, 255, 255, .02) 40px);
    }

    .ayat-inner {
      position: relative;
      z-index: 2;
      display: flex;
      align-items: center;
      gap: 24px;
      flex-wrap: wrap;
    }

    .ayat-content {
      flex: 1;
      min-width: 220px;
    }

    .ayat-tag {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      background: rgba(13, 148, 136, .18);
      border: 1px solid rgba(13, 148, 136, .3);
      color: #5eead4;
      border-radius: 20px;
      padding: 2px 10px;
      font-size: .63rem;
      font-weight: 700;
      letter-spacing: 1px;
      text-transform: uppercase;
      margin-bottom: 12px;
    }

    .ayat-arabic {
      font-family: 'Amiri', serif;
      font-size: 1.55rem;
      line-height: 1.8;
      color: #fff;
      direction: rtl;
      text-align: right;
      margin-bottom: 10px;
      text-shadow: 0 0 40px rgba(94, 234, 212, .15);
    }

    .ayat-translations {
      display: flex;
      flex-direction: column;
      gap: 4px;
      border-left: 2px solid rgba(94, 234, 212, .25);
      padding-left: 12px;
      margin-bottom: 10px;
    }

    .ayat-english {
      font-size: .88rem;
      color: rgba(255, 255, 255, .8);
      font-style: italic;
      font-family: 'Georgia', serif;
      line-height: 1.5;
    }

    .ayat-urdu {
      font-size: .85rem;
      color: rgba(255, 255, 255, .5);
      direction: rtl;
      text-align: right;
      font-family: 'Noto Nastaliq Urdu', serif;
      line-height: 1.8;
    }

    .ayat-ref {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      font-size: .68rem;
      font-weight: 600;
      color: rgba(255, 255, 255, .3);
      text-transform: uppercase;
    }

    .ayat-ref i {
      color: rgba(94, 234, 212, .5);
    }

    .ayat-deco {
      flex-shrink: 0;
      text-align: center;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 6px;
    }

    .ayat-deco-ring {
      width: 64px;
      height: 64px;
      border-radius: 50%;
      border: 1.5px solid rgba(94, 234, 212, .2);
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
    }

    .ayat-deco-ring::before {
      content: '';
      position: absolute;
      inset: 5px;
      border-radius: 50%;
      border: 1px solid rgba(94, 234, 212, .1);
    }

    .ayat-deco-ring span {
      font-size: .7rem;
      font-weight: 700;
      color: rgba(94, 234, 212, .7);
      text-align: center;
      line-height: 1.2;
    }

    .ayat-deco-lbl {
      font-size: .58rem;
      color: rgba(255, 255, 255, .2);
      text-transform: uppercase;
      letter-spacing: .8px;
    }

    @media(max-width:600px) {
      .ayat-banner {
        padding: 20px 16px;
      }

      .ayat-arabic {
        font-size: 1.25rem;
      }

      .ayat-deco {
        display: none;
      }
    }
  </style>

  <div class="breadcome-area">
    <div class="container-fluid">
      <div class="ayat-banner">
        <div class="ayat-inner">
          <div class="ayat-content">
            <div class="ayat-tag"><i class="bi bi-stars"></i> Ayat of the Day</div>
            <div class="ayat-arabic"><?= htmlspecialchars($ayat['arabic']) ?></div>
            <div class="ayat-translations">
              <div class="ayat-english">"<?= htmlspecialchars($ayat['english']) ?>"</div>
              <div class="ayat-urdu"><?= htmlspecialchars($ayat['urdu']) ?></div>
            </div>
            <div class="ayat-ref"><i class="bi bi-book-half"></i> <?= htmlspecialchars($ayat['ref']) ?></div>
          </div>
          <div class="ayat-deco">
            <div class="ayat-deco-ring"><span>آیت<br>روز</span></div>
            <div class="ayat-deco-lbl">Daily Verse</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Row 1: Summary Cards -->
  <div class="container-fluid mt-4">
    <div class="row g-3">

      <!-- Card 1: Active Dars Areas -->
      <div class="col-lg-3 col-md-6">
        <div class="nk-pm-card">
          <div id="pmRadial1" style="height:120px"></div>
          <div class="nk-pm-card-value"><?= $totalAreas ?></div>
          <div class="nk-pm-card-label">Active Dars Areas</div>
          <?php if ($newAreasThisMonth > 0): ?>
            <span class="badge bg-success mt-1"><i class="bi bi-arrow-up"></i> +<?= $newAreasThisMonth ?> this month</span>
          <?php else: ?>
            <span class="badge bg-secondary mt-1"><i class="bi bi-geo-alt"></i> All Active</span>
          <?php endif; ?>
        </div>
      </div>

      <!-- Card 2: Total Users -->
      <div class="col-lg-3 col-md-6">
        <div class="nk-pm-card">
          <div id="pmRadial2" style="height:120px"></div>
          <div class="nk-pm-card-value"><?= $totalUsers ?></div>
          <div class="nk-pm-card-label">Total Members</div>
          <?php if ($newUsersThisMonth > 0): ?>
            <span class="badge bg-info mt-1"><i class="bi bi-person-plus"></i> +<?= $newUsersThisMonth ?> this month</span>
          <?php else: ?>
            <span class="badge bg-secondary mt-1"><i class="bi bi-people"></i> All Members</span>
          <?php endif; ?>
        </div>
      </div>

      <!-- Card 3: Committee Members -->
      <div class="col-lg-3 col-md-6">
        <div class="nk-pm-card">
          <div id="pmRadial3" style="height:120px"></div>
          <div class="nk-pm-card-value"><?= $totalCommittee ?></div>
          <div class="nk-pm-card-label">Committee Members</div>
          <span class="badge bg-success mt-1"><i class="bi bi-shield-check"></i> Active Roles</span>
        </div>
      </div>

      <!-- Card 4: Workshops This Month -->
      <div class="col-lg-3 col-md-6">
        <div class="nk-pm-card">
          <div id="pmRadial4" style="height:120px"></div>
          <div class="nk-pm-card-value"><?= $workshopsThisMonth ?></div>
          <div class="nk-pm-card-label">Workshops This Month</div>
          <?php if ($workshopsThisMonth > 0): ?>
            <span class="badge bg-primary mt-1"><i class="bi bi-calendar-check"></i> Conducted</span>
          <?php else: ?>
            <span class="badge bg-secondary mt-1"><i class="bi bi-calendar-x"></i> None yet</span>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>

<div class="container-fluid mt-4">
  <div class="traffic-analysis-area">
    <div class="container-fluid">
      <div class="row">

        <!-- Card 1: Quran Attendance -->
        <div class="col-lg-4 col-md-4 col-sm-4 col-12">
          <div class="white-box tranffic-als-inner">
            <h3 class="box-title">
              <?php if ($quranWeekChangePct >= 0): ?>
                <small class="float-end m-t-10 text-success last-month-sc cl-one">
                  <i class="bi bi-arrow-up"></i> +<?= $quranWeekChangePct ?>% last week
                </small>
              <?php else: ?>
                <small class="float-end m-t-10 text-danger last-month-sc cl-one">
                  <i class="bi bi-arrow-down"></i> <?= $quranWeekChangePct ?>% last week
                </small>
              <?php endif; ?>
              Quran Attendance
            </h3>
            <div class="stats-row">
              <div class="stat-item">
                <h6>Consistent (4+ days)</h6>
                <b><?= $quranActiveLastWeek ?> members</b>
              </div>
              <div class="stat-item">
                <h6>Consistency %</h6>
                <b><?= $quranConsistencyPct ?>%</b>
              </div>
              <div class="stat-item">
                <h6>Last Week</h6>
                <b><?= $quranTotalLastWeek ?> active</b>
              </div>
            </div>
            <div id="sparkline8"></div>
          </div>
        </div>

        <!-- Card 2: Dars Attendance -->
        <div class="col-lg-4 col-md-4 col-sm-4 col-12">
          <div class="white-box tranffic-als-inner res-mg-t-30">
            <h3 class="box-title">
              <?php if ($darsWeekChangePct >= 0): ?>
                <small class="float-end m-t-10 text-success last-month-sc cl-two">
                  <i class="bi bi-arrow-up"></i> +<?= $darsWeekChangePct ?>% last week
                </small>
              <?php else: ?>
                <small class="float-end m-t-10 text-danger last-month-sc cl-two">
                  <i class="bi bi-arrow-down"></i> <?= $darsWeekChangePct ?>% last week
                </small>
              <?php endif; ?>
              Dars Attendance
            </h3>
            <div class="stats-row">
              <div class="stat-item">
                <h6>Present Last Week</h6>
                <b><?= $darsPresentLastWeek ?> members</b>
              </div>
              <div class="stat-item">
                <h6>Attendance %</h6>
                <b><?= $darsAttendancePct ?>%</b>
              </div>
              <div class="stat-item">
                <h6>Total Records</h6>
                <b><?= $darsTotalLastWeek ?></b>
              </div>
            </div>
            <div id="sparkline9"></div>
          </div>
        </div>

        <!-- Card 3: Combined Overview -->
        <div class="col-lg-4 col-md-4 col-sm-4 col-12">
          <div class="white-box tranffic-als-inner res-mg-t-30">
            <h3 class="box-title">
              <small class="float-end m-t-10 text-success last-month-sc cl-three">
                <i class="bi bi-activity"></i> This week
              </small>
              Attendance Overview
            </h3>
            <div class="stats-row">
              <div class="stat-item">
                <h6>Quran Active</h6>
                <b><?= $quranActiveLastWeek ?></b>
              </div>
              <div class="stat-item">
                <h6>Dars Present</h6>
                <b><?= $darsPresentLastWeek ?></b>
              </div>
              <div class="stat-item">
                <h6>Dars Rate</h6>
                <b><?= $darsAttendancePct ?>%</b>
              </div>
            </div>
            <div id="sparkline10"></div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>
  <!-- Row 2: Mini Kanban + Activity Timeline -->
  <!-- <div class="container-fluid mt-4">
    <div class="row g-3">
      <div class="col-lg-8">
        <div class="white-box">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="box-title mb-0">Sprint 14 Tasks</h3>
            <a href="kanban-board.html" class="text-info text-decoration-none" style="font-size:13px">View Board <i class="bi bi-arrow-right"></i></a>
          </div>
          <div class="nk-pm-kanban-compact d-flex gap-3" style="overflow-x:auto">
           
            <div class="kanban-column flex-fill" style="min-width:200px">
              <div class="kanban-column-header d-flex align-items-center mb-2">
                <span class="rounded-circle d-inline-block me-2" style="width:8px;height:8px;background:#6c757d"></span>
                <strong class="text-white" style="font-size:13px">Todo</strong>
                <span class="badge bg-secondary ms-auto">3</span>
              </div>
              <div class="kanban-card-list" style="min-height:120px">
                <div class="kanban-card mb-2">
                  <div class="kanban-card-labels mb-1"><span class="badge" style="background:#775dd0;font-size:10px">Design</span></div>
                  <div class="text-white" style="font-size:13px">Update landing page hero</div><small class="text-secondary">Due Feb 18</small>
                </div>
                <div class="kanban-card mb-2">
                  <div class="kanban-card-labels mb-1"><span class="badge" style="background:#008ffb;font-size:10px">Backend</span></div>
                  <div class="text-white" style="font-size:13px">API rate limiting middleware</div><small class="text-secondary">Due Feb 19</small>
                </div>
              </div>
            </div>
          
            <div class="kanban-column flex-fill" style="min-width:200px">
              <div class="kanban-column-header d-flex align-items-center mb-2">
                <span class="rounded-circle d-inline-block me-2" style="width:8px;height:8px;background:#008ffb"></span>
                <strong class="text-white" style="font-size:13px">In Progress</strong>
                <span class="badge bg-primary ms-auto">2</span>
              </div>
              <div class="kanban-card-list" style="min-height:120px">
                <div class="kanban-card mb-2">
                  <div class="kanban-card-labels mb-1"><span class="badge" style="background:#ff4560;font-size:10px">Bug Fix</span></div>
                  <div class="text-white" style="font-size:13px">Fix payment gateway timeout</div><small class="text-secondary">Due Feb 15</small>
                </div>
              </div>
            </div>
         
            <div class="kanban-column flex-fill" style="min-width:200px">
              <div class="kanban-column-header d-flex align-items-center mb-2">
                <span class="rounded-circle d-inline-block me-2" style="width:8px;height:8px;background:#00e396"></span>
                <strong class="text-white" style="font-size:13px">Done</strong>
                <span class="badge bg-success ms-auto">3</span>
              </div>
              <div class="kanban-card-list" style="min-height:120px">
                <div class="kanban-card mb-2">
                  <div class="kanban-card-labels mb-1"><span class="badge" style="background:#008ffb;font-size:10px">Backend</span></div>
                  <div class="text-white" style="font-size:13px">Database migration script</div><small class="text-secondary">Completed Feb 12</small>
                </div>
                <div class="kanban-card mb-2">
                  <div class="kanban-card-labels mb-1"><span class="badge" style="background:#feb019;font-size:10px">DevOps</span></div>
                  <div class="text-white" style="font-size:13px">CI/CD pipeline optimization</div><small class="text-secondary">Completed Feb 10</small>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="white-box">
          <h3 class="box-title">Recent Activity</h3>
          <div data-simplebar style="max-height:350px">
            <div class="d-flex align-items-start mb-3">
              <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:36px;height:36px;background:#253a5c;color:#00e396;font-size:14px"><i class="bi bi-check-lg"></i></div>
              <div class="ms-3">
                <div class="text-white" style="font-size:13px">Session held in Satellite Town</div><small class="text-secondary">2 hours ago</small>
              </div>
            </div>
            <div class="d-flex align-items-start mb-3">
              <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:36px;height:36px;background:#253a5c;color:#008ffb;font-size:14px"><i class="bi bi-person-plus"></i></div>
              <div class="ms-3">
                <div class="text-white" style="font-size:13px">New member joined Bahria Area</div><small class="text-secondary">5 hours ago</small>
              </div>
            </div>
            <div class="d-flex align-items-start mb-3">
              <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:36px;height:36px;background:#253a5c;color:#feb019;font-size:14px"><i class="bi bi-megaphone"></i></div>
              <div class="ms-3">
                <div class="text-white" style="font-size:13px">Workshop conducted in PWD</div><small class="text-secondary">Yesterday</small>
              </div>
            </div>
            <div class="d-flex align-items-start mb-3">
              <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:36px;height:36px;background:#253a5c;color:#775dd0;font-size:14px"><i class="bi bi-book"></i></div>
              <div class="ms-3">
                <div class="text-white" style="font-size:13px">Quran class attendance recorded</div><small class="text-secondary">Yesterday</small>
              </div>
            </div>
            <div class="d-flex align-items-start">
              <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:36px;height:36px;background:#253a5c;color:#ff4560;font-size:14px"><i class="bi bi-flag"></i></div>
              <div class="ms-3">
                <div class="text-white" style="font-size:13px">New dars area added</div><small class="text-secondary">2 days ago</small>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div> -->

  <!-- Row 3: Charts + Upcoming Events -->
  <!-- <div class="container-fluid mt-4">
    <div class="row g-3">
      <div class="col-lg-6">
        <div class="white-box">
          <h3 class="box-title">Activity Breakdown</h3>
          <div id="pmTaskStatusChart" style="height:300px"></div>
          <div class="d-flex justify-content-around text-center mt-2 pt-2" style="border-top:1px solid #1e3154">
            <div>
              <div class="text-white fw-bold"><?= $totalUsers ?></div><small>Total Users</small>
            </div>
            <div>
              <div class="text-success fw-bold"><?= $totalAreas ?></div><small>Areas</small>
            </div>
            <div>
              <div class="text-warning fw-bold"><?= $totalCommittee ?></div><small>Committee</small>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="white-box">
          <h3 class="box-title">Upcoming Events</h3>
          <div id="pmCalendar" style="height:350px"></div>
        </div>
      </div>
    </div>
  </div> -->

  <!-- Row 4: Quick Actions -->
  <!-- <div class="container-fluid mt-4 mb-4">
    <div class="row g-3">
      <div class="col-lg-8">
        <div class="white-box">
          <h3 class="box-title">Sprint Burndown</h3>
          <div id="pmBurndownChart" style="height:320px"></div>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="white-box">
          <h3 class="box-title">Quick Actions</h3>
          <div class="row g-2">
            <div class="col-6"><a href="darsArea.php" class="nk-pm-quick-action" style="background:rgba(0,227,150,.1);color:#00e396"><i class="bi bi-geo-alt" style="font-size:24px"></i><span>Dars Areas</span></a></div>
            <div class="col-6"><a href="users.php" class="nk-pm-quick-action" style="background:rgba(0,143,251,.1);color:#008ffb"><i class="bi bi-people" style="font-size:24px"></i><span>Members</span></a></div>
            <div class="col-6"><a href="events.php" class="nk-pm-quick-action" style="background:rgba(254,176,25,.1);color:#feb019"><i class="bi bi-calendar-event" style="font-size:24px"></i><span>Events</span></a></div>
            <div class="col-6"><a href="namazAttendance.php" class="nk-pm-quick-action" style="background:rgba(119,93,208,.1);color:#775dd0"><i class="bi bi-moon-stars" style="font-size:24px"></i><span>Namaz Track</span></a></div>
            <div class="col-6"><a href="darsAttendance.php" class="nk-pm-quick-action" style="background:rgba(255,69,96,.1);color:#ff4560"><i class="bi bi-journal-check" style="font-size:24px"></i><span>Dars Attend.</span></a></div>
            <div class="col-6"><a href="quranAttendance.php" class="nk-pm-quick-action" style="background:rgba(6,182,212,.1);color:#06b6d4"><i class="bi bi-book" style="font-size:24px"></i><span>Quran Track</span></a></div>
          </div>
        </div>
      </div>
    </div>
  </div> -->

  <?php include "footer.php"; ?>

  <script>
    var pmAreaCount = <?= (int)$totalAreas ?>;
    var pmTotalUsers = <?= (int)$totalUsers ?>;
    var pmCommittee = <?= (int)$totalCommittee ?>;
    var pmWorkshops = <?= (int)$workshopsThisMonth ?>;

    // Trend data for sparklines
    var quranTrendData = <?= $quranTrendJson ?>;
    var darsTrendData = <?= $darsTrendJson ?>;

    // Combined: sum of both for overview card
    var combinedTrend = quranTrendData.map(function(v, i) {
        return v + (darsTrendData[i] || 0);
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script src="https://cdn.jsdelivr.net/npm/simplebar@6.2.7/dist/simplebar.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts@5.3.6/dist/apexcharts.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.20/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
<script src="js/main.js"></script>

</body>
</html>