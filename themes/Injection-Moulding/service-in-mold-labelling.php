<?php
/**
 * Template Name: Service - In Mold Labelling
 * Template Post Type: page
 */
get_header();
?>
<script>
// Scroll Animation Logic
    window.addEventListener('scroll', () => {
      const line = document.getElementById('line-progress');
      const rows = document.querySelectorAll('.timeline-row');
      const dots = document.querySelectorAll('.dot');
      const innerDots = document.querySelectorAll('.inner-dot');

      const scrollPos = window.scrollY + (window.innerHeight * 0.5);
      const section = line.closest('section');
      const sectionTop = section.offsetTop;
      const sectionHeight = section.offsetHeight;

      // Line Progress Calculation
      let progress = ((scrollPos - sectionTop) / sectionHeight) * 100;
      line.style.height = Math.min(Math.max(progress, 0), 100) + '%';

      // Activate Dots & Glowing Core
      rows.forEach((row, i) => {
        const rowTop = row.getBoundingClientRect().top + window.scrollY;
        if (scrollPos > rowTop) {
          // Active Glow State
          dots[i].classList.add('border-[#C8FF00]', 'shadow-[0_0_20px_rgba(200,255,0,0.6)]');
          innerDots[i].classList.replace('bg-white/10', 'bg-[#C8FF00]');
          innerDots[i].classList.add('shadow-[0_0_10px_#C8FF00]');
        } else {
          // Inactive State
          dots[i].classList.remove('border-[#C8FF00]', 'shadow-[0_0_20px_rgba(200,255,0,0.6)]');
          innerDots[i].classList.replace('bg-[#C8FF00]', 'bg-white/10');
          innerDots[i].classList.remove('shadow-[0_0_10px_#C8FF00]');
        }
      });
    });
</script>
<main class="im-page-main">
	<?php
// Featured image URL uthane ke liye
$hero_bg = get_the_post_thumbnail_url(get_the_ID(), 'full');
// Agar image nahi hai toh default image lagane ke liye
if (!$hero_bg) {
    $hero_bg = get_template_directory_uri() . '/assets/images/Services/Injection-Moulding.png';
}
?>

<section class="relative min-h-[265px] w-full flex flex-col items-center bg-center bg-cover"
    style="background-image: url('<?php echo esc_url( get_template_directory_uri() . '/assets/images/SmallMediumBatchRuns/header.png' ); ?>');"

    <!-- Aapka Navbar (Header) yahan rahega jo aapne diya hai -->
    <!-- Note: Navbar ko 'fixed' ki jagah 'absolute' rakhein agar sirf is section mein chahiye -->
    
    <div class="absolute bottom-10 left-0 w-full px-6 lg:px-20 font-regular font-[Helvetica] text-[20px]">
      <div class="max-w-7xl mx-auto flex items-center gap-2 text-white font-medium">
        <a href="<?php echo home_url(); ?>" class="hover:underline">Home</a>
        <span class="text-white">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path d="M9 5l7 7-7 7" stroke-width="2"></path>
          </svg>
        </span>
        <a href="#" class="hover:underline">Services</a>
        <span class="text-white mx-1">/</span>
        <span class="text-[#C8FF00] hover:underline"><?php the_title(); ?></span>
      </div>
    </div>
</section>

 <!-- ========= Glass to Plastic Conversion start ======================= -->
  <section class="reveal py-20 bg-[#F5F6FA]">
    <div class="max-w-7xl mx-auto px-6">
      <div class="flex flex-col lg:flex-row items-center gap-16">

        <div class="reveal  mx-auto">
          <div class="reveal inline-flex items-center gap-2 px-3 py-1 bg-[#D7FF80]/50 rounded-full mb-6">
            <span class="w-2 h-2 rounded-full bg-[#153F45]"></span>
            <span class="text-[16] font-medium font-[Poppins] text-[#19191A]  tracking-wide">In-Mold Labelling</span>
          </div>

          <h2 class="reveal text-[40px] font-medium font-[Helvetica] text-[#19191A] mb-2">
            In-Mold Labelling (IML)
          </h2>

          <p class="reveal text-[#767C8C] text-[16px] font-medium font-[Montserrat] leading-relaxed mb-8">
            In-Mold Labelling (IML) is an advanced manufacturing process used in injection moulding where a printed
            label is placed directly inside the mould before the plastic part is formed. During the moulding process,
            the label bonds permanently with the plastic, becoming an integral part of the finished product.This
            technique produces high-quality, durable labels that cannot peel, wrinkle, or fade over time.
          </p>
        </div>
      </div>
    </div>
  </section>
  <!-- ========= Glass to Plastic Conversion end ======================= -->

  <!-- ================= Moulding Process start================= -->
  <section class="relative py-20 overflow-hidden bg-[#00191C]">
    <div class="reveal text-center mb-32 px-6">
      <p class="text-[#C8FF00] font-medium text-[20px] tracking-wide font-[Helvetica] mb-4">The Process</p>
      <h2 class="font-medium text-[40px] font-[Helvetica] text-[#FFFFFF] mb-2">
        How In-Mold Labelling <span class="text-[#C8FF00] "> Works</span>
      </h2>
      <p class="text-[#FFFFFF] max-w-7xl mx-auto text-[#18px] font-[Montserrat] font-regular">
        A seamless four-step process that integrates labelling directly into the moulding cycle.
      </p>
    </div>

    <div class="reveal max-w-6xl mx-auto relative px-6">

      <div
        class="hidden lg:block absolute left-1/2 -translate-x-1/2 top-0 h-full w-[14px] bg-glassBg/30 border border-white/5 rounded-full overflow-hidden shadow-[inset_0_0_10px_rgba(0,0,0,0.5)]">
        <div id="line-progress"
          class="w-full h-0 bg-[#C8FF00]/30 rounded-full transition-all duration-500 ease-out relative shadow-[0_0_20px_rgba(200,255,0,0.6)]">
          <div
            class="absolute left-1/2 -translate-x-1/2 w-[2px] h-full bg-[#C8FF00] shadow-[0_0_15px_#C8FF00,0_0_5px_#fff]">
          </div>
        </div>
      </div>

      <div class="space-y-24 lg:space-y-1">

        <div class="timeline-row relative flex flex-col lg:flex-row items-center group reveal">
          <div class="w-full lg:w-[551px] lg:h-[280px] lg:pr-20 ">
            <div
              class="relative group bg-[#616161]/10 rounded-[28px] p-6 bg-[linear-gradient(to_top,rgba(200,255,0,0.15)_0%,rgba(97,97,97,0.09)_60%,transparent_100%)] transition-all duration-500 group-hover:-translate-y-2 group-hover:border-lime/30 group-hover:bg-glassBg/50">
              <!-- Neon Line -->
              <div
                class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[90px] h-[1.5px] bg-[linear-gradient(90deg,transparent,#bef264,transparent)] shadow-[0_0_8px_rgba(190,242,100,0.5)]">
              </div>
              <!-- Border Glow -->


              <div class="flex items-center justify-between">
                <div
                  class="w-10 h-10 bg-[#BEF264]/10 border border-[#BEF264] rounded-lg flex items-center justify-center shadow-[0_0_15px_rgba(200,225,100,0.6)] ">
                  <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/Frame 2147230828.png" alt="Mould Clamp" class=" shadow-md" />
                </div>
                <p class="text-transparent font-bold text-[26px] font-[Poppins] tracking-wide"
                  style="-webkit-text-stroke: 2px #A8AEBF; opacity: 0.5;">Step 1</p>
              </div>
              <h3 class="text-[#EBEDF0] font-semibold text-[20px] font-[Poppins] leading-relaxed py-2">
                Label Placement
              </h3>
              <p class="text-[#FFFFFF] font-[400] text-[18px] font-[Montserrat] text-medium leading-relaxed">
                A pre-printed label is placed inside the injection mould before the plastic material is injected. The
                label is positioned precisely to ensure accurate alignment on the finished product.
              </p>
            </div>
          </div>

          <div
            class="dot hidden lg:flex absolute left-1/2 -translate-x-1/2 w-11 h-11 rounded-full bg-darkGreen border-2 border-lime/20 items-center justify-center z-10 transition-all duration-500">
            <div class="inner-dot w-3 h-3 rounded-full bg-white/10 transition-all duration-500"></div>
          </div>
          <div class="hidden lg:block w-1/2"></div>
        </div>

        <div class="timeline-row relative flex flex-col lg:flex-row items-center group reveal">
          <div class="hidden lg:block w-1/2"></div>
          <div
            class="dot hidden lg:flex absolute left-1/2 -translate-x-1/2 w-11 h-11 rounded-full bg-darkGreen border-2 border-lime/20 items-center justify-center z-10 transition-all duration-500">
            <div class="inner-dot w-3 h-3 rounded-full bg-white/10 transition-all duration-500"></div>
          </div>
          <div class="w-full lg:w-[551px] lg:h-[280px] lg:pl-20 ">
            <div
              class="relative group bg-[#616161]/10 rounded-[28px] p-6 bg-[linear-gradient(to_top,rgba(200,255,0,0.15),rgba(97,97,97,0.09)_60%,transparent_100%)] transition-all duration-500 group-hover:-translate-y-2 group-hover:border-lime/30 group-hover:bg-glassBg/50">
              <!-- Neon Line -->
              <div
                class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[90px] h-[1.5px] bg-[linear-gradient(90deg,transparent,#bef264,transparent)] shadow-[0_0_8px_rgba(190,242,100,0.5)]">
              </div>
              <!-- Border Glow -->

              <div class="flex items-center justify-between">
                <div
                  class="w-10 h-10 bg-[#BEF264]/10 border border-[#BEF264] rounded-lg flex items-center justify-center shadow-[0_0_15px_rgba(200,225,100,0.6)] ">
                  <img src="<?php echo get_template_directory_uri(); ?>/assets/images/Services/icon/EjectionFinishedPart.png" alt="Ejection Finished Part"
                    class=" shadow-md" />
                </div>
                <p class="text-transparent font-bold text-[26px] font-[Poppins] tracking-wide"
                  style="-webkit-text-stroke: 2px #A8AEBF; opacity: 0.5;">Step 2</p>
              </div>
              <h3 class="text-[#EBEDF0] font-semibold text-[20px] font-[Poppins] leading-relaxed py-2">
                Injection Moulding Process
              </h3>
              <p class="text-[#FFFFFF] font-[400] text-[18px] font-[Montserrat] text-medium leading-relaxed">
               Molten plastic is injected into the mould where it fuses with the label. As the plastic cools and solidifies, the label becomes permanently embedded within the product.
              </p>
            </div>
          </div>
        </div>

        <div class="timeline-row relative flex flex-col lg:flex-row items-center group reveal">
          <div class="w-full lg:w-[551px] lg:h-[280px] lg:pr-20 ">
            <div
              class="relative group bg-[#616161]/10 rounded-[28px] p-6 bg-[linear-gradient(to_top,rgba(200,255,0,0.15)_0%,rgba(97,97,97,0.09)_60%,transparent_100%)] transition-all duration-500 group-hover:-translate-y-2 group-hover:border-lime/30 group-hover:bg-glassBg/50">
              <!-- Neon Line -->
              <div
                class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[90px] h-[1.5px] bg-[linear-gradient(90deg,transparent,#bef264,transparent)] shadow-[0_0_8px_rgba(190,242,100,0.5)]">
              </div>
              <!-- Border Glow -->
              <div class="flex items-center justify-between">
                <div
                  class="w-10 h-10 bg-[#BEF264]/10 border border-[#BEF264] rounded-lg flex items-center justify-center shadow-[0_0_15px_rgba(200,225,100,0.6)] ">
                  <img src="<?php echo get_template_directory_uri(); ?>/assets/images//Services/icon/Injection.png" alt="Injection" class=" shadow-md" />
                </div>
                <p class="text-transparent font-bold text-[26px] font-[Poppins] tracking-wide"
                  style="-webkit-text-stroke: 2px #A8AEBF; opacity: 0.5;">Step 3</p>
              </div>

              <h3 class="text-[#EBEDF0] font-semibold text-[20px] font-[Poppins] leading-relaxed py-2">
               Permanent Bond
              </h3>
              <p class="text-[#FFFFFF] font-[400] text-[18px] font-[Montserrat] text-medium leading-relaxed">
               During the moulding process, the label bonds permanently with the plastic, becoming an integral part of the finished product that cannot peel, wrinkle, or fade.
              </p>
            </div>
          </div>
          <div
            class="dot hidden lg:flex absolute left-1/2 -translate-x-1/2 w-11 h-11 rounded-full bg-darkGreen border-2 border-lime/20 items-center justify-center z-10 transition-all duration-500">
            <div class="inner-dot w-3 h-3 rounded-full bg-white/10 transition-all duration-500"></div>
          </div>
          <div class="hidden lg:block w-1/2"></div>
        </div>

        <div class="timeline-row relative flex flex-col lg:flex-row items-center group reveal">
          <div class="hidden lg:block w-1/2"></div>
          <div
            class="dot hidden lg:flex absolute left-1/2 -translate-x-1/2 w-11 h-11 rounded-full bg-darkGreen border-2 border-lime/20 items-center justify-center z-10 transition-all duration-500">
            <div class="inner-dot w-3 h-3 rounded-full bg-white/10 transition-all duration-500"></div>
          </div>
          <div class="w-full lg:w-[551px] lg:h-[280px] lg:pl-20 ">
            <div
              class="relative group bg-[#616161]/10 rounded-[28px] p-6 bg-[linear-gradient(to_top,rgba(200,255,0,0.15)_0%,rgba(97,97,97,0.09)_60%,transparent_100%)] transition-all duration-500 group-hover:-translate-y-2 group-hover:border-lime/30 group-hover:bg-glassBg/50">
              <!-- Neon Line -->
              <div
                class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[90px] h-[1.5px] bg-[linear-gradient(90deg,transparent,#bef264,transparent)] shadow-[0_0_8px_rgba(190,242,100,0.5)]">
              </div>
              <!-- Border Glow -->
              <div class="flex items-center justify-between">
                <div
                  class="w-10 h-10 bg-[#BEF264]/10 border border-[#BEF264] rounded-lg flex items-center justify-center shadow-[0_0_15px_rgba(200,225,100,0.6)] ">
                  <img src="<?php echo get_template_directory_uri(); ?>/assets/images/Services/icon/CoolingSolidifying.png" alt="Cooling / Solidifying"
                    class=" shadow-md" />

                </div>
                <p class="text-transparent font-bold text-[26px] font-[Poppins] tracking-wide"
                  style="-webkit-text-stroke: 2px #A8AEBF; opacity: 0.5;">Step 4</p>
              </div>



              <h3 class="text-[#EBEDF0] font-semibold text-[20px] font-[Poppins] leading-relaxed py-2">
               Finished Product
              </h3>
              <p class="text-[#FFFFFF] font-[400] text-[18px] font-[Montserrat] text-medium leading-relaxed">
               Once the mould opens, the finished component is ejected with the label fully integrated into the surface, creating a seamless and durable finish.
              </p>
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>
 <!-- ================= why chose us start ================= -->
  <section class="reveal bg-[#F5F6FA] py-16 sm:py-20 lg:py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
      <!-- TOP TEXT -->
      <p
        class="inline-flex items-center gap-2 rounded-full bg-[#d8efad] px-5 py-1 sm:text-[13px] text-[16px] font-[Poppins] font-medium text-[#19191A] tracking-wide">
        <span class="h-2 w-2 rounded-full bg-[#19191A]"></span>
        Advantages
      </p>

      <!-- HEADING -->
      <h2
        class="reveal text-[25px] sm:text-2xl md:text-[40px] tracking-wide font-bold mt-2 font-[Helvetica] text-[#19191A] leading-tight mx-auto pt-3 tracking-wide">
       Benefits of IML
      </h2>

      <!-- DESCRIPTION -->
      <p
        class="reveal text-[#767C8C] font-medium font-[Montserrat] mt-4 sm:mt-6 text-[16px] max-w-7xl mx-auto leading-relaxed">
       Why leading manufacturers choose In-Mold Labelling for premium product branding.
      </p>

      <!-- CARDS -->
      <div class="reveal grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-4 sm:gap-6 mt-5 sm:mt-14">
        <!-- CARD 1 -->
        <div
          class="group relative bg-[#FFFFFF] p-8  sm:rounded-2xl sm:p-6 lg:p-8 text-left shadow-lg transition-all duration-300 ease-in-out hover:bg-[#0f3032] cursor-pointer">
          <!-- ICON -->
          <div
            class="w-12 h-12 flex items-center justify-center rounded-lg bg-[#F0F1F5] mb-6 transition-colors duration-300 group-hover:bg-[#fbfbfb1f]">
            <div class="w-6 h-6 bg-black group-hover:bg-[#d4ff00] transition-colors duration-300" style="
                 mask-image: url('<?php echo get_template_directory_uri(); ?>/assets/images/icons/Durable\ Labels.png'); 
        -webkit-mask-image: url('<?php echo get_template_directory_uri(); ?>/assets/images/icons/Durable\ Labels.png'); 
        mask-repeat: no-repeat; 
        mask-size: contain; 
        -webkit-mask-size: contain; 
        mask-position: center;
        -webkit-mask-position: center;
                "></div>
          </div>

          <!-- TITLE -->
          <h3
            class="text-[24px] font-medium font-[Helvetica] tracking-wide text-[#15181F] mb-2 sm:mb-3 transition-colors duration-300 group-hover:text-white leading-tight">
           Durable Labels
          </h3>

          <!-- TEXT -->
          <p
            class="text-[#474C59] text-[18px] font-[Montserrat] font-regular leading-relaxed transition-colors duration-300 group-hover:text-white leading-tight">
           Because the label becomes part of the product, it is highly resistant to scratches, moisture, and wear.
          </p>
        </div>
        <!-- CARD 2 -->
        <div
          class="group relative bg-[#FFFFFF] p-8 rounded-xl sm:rounded-2xl sm:p-6 lg:p-8 text-left shadow-lg transition-all duration-300 ease-in-out hover:bg-[#0f3032] cursor-pointer">
          <!-- ICON -->
          <div
            class="w-12 h-12 flex items-center justify-center rounded-lg bg-[#F0F1F5] mb-6 transition-colors duration-300 group-hover:bg-[#fbfbfb1f]">
            <div class="w-6 h-6 bg-black group-hover:bg-[#d4ff00] transition-colors duration-300" style="
                  mask-image: url('<?php echo get_template_directory_uri(); ?>/assets/images/icons/High-Quality\ Appearance.png'); 
        -webkit-mask-image: url('<?php echo get_template_directory_uri(); ?>/assets/images/icons/High-Quality\ Appearance.png'); 
        mask-repeat: no-repeat; 
        mask-size: contain; 
        -webkit-mask-size: contain; 
        mask-position: center;
        -webkit-mask-position: center;
                "></div>
          </div>

          <!-- TITLE -->
          <h3
            class="text-[24px] font-medium font-[Helvetica] tracking-wide text-[#15181F] mb-2 sm:mb-3 transition-colors duration-300 group-hover:text-white leading-tight">
           High-Quality Appearance
          </h3>

          <!-- TEXT -->
          <p
            class="text-[#474C59] text-[18px] font-[Montserrat] font-regular leading-relaxed transition-colors duration-300 group-hover:text-white leading-tight">
           IML allows for vibrant colours, detailed graphics, and professional product branding directly on the moulded part.
          </p>
        </div>
        <!-- CARD 3 -->
        <div
          class="group relative bg-[#FFFFFF] p-8 rounded-xl sm:rounded-2xl sm:p-6 lg:p-8 text-left shadow-lg transition-all duration-300 ease-in-out hover:bg-[#0f3032] cursor-pointer">
          <!-- ICON -->
          <div
            class="w-12 h-12 flex items-center justify-center rounded-lg bg-[#F0F1F5] mb-6 transition-colors duration-300 group-hover:bg-[#fbfbfb1f]">
            <div class="w-6 h-6 bg-black group-hover:bg-[#d4ff00] transition-colors duration-300" style="
                 mask-image: url('<?php echo get_template_directory_uri(); ?>/assets/images/icons/Increased\ Efficiency.png'); 
        -webkit-mask-image: url('<?php echo get_template_directory_uri(); ?>/assets/images/icons/Increased\ Efficiency.png'); 
        mask-repeat: no-repeat; 
        mask-size: contain; 
        -webkit-mask-size: contain; 
        mask-position: center;
        -webkit-mask-position: center;
                "></div>
          </div>

          <!-- TITLE -->
          <h3
            class="text-[24px] font-medium font-[Helvetica] tracking-wide text-[#15181F] mb-2 sm:mb-3 transition-colors duration-300 group-hover:text-white leading-tight">
           Increased Efficiency
          </h3>

          <!-- TEXT -->
          <p
            class="text-[#474C59] text-[18px] font-[Montserrat] font-regular leading-relaxed transition-colors duration-300 group-hover:text-white leading-tight">
            Labelling and moulding occur in a single production step, reducing additional processing and improving production efficiency.
          </p>
        </div>

        <!-- CARD 4 -->
        <div
          class="group relative bg-[#FFFFFF] p-8 rounded-xl sm:rounded-2xl sm:p-6 lg:p-8 text-left shadow-lg transition-all duration-300 ease-in-out hover:bg-[#0f3032] cursor-pointer">
          <!-- ICON -->
          <div
            class="w-12 h-12 flex items-center justify-center rounded-lg bg-[#F0F1F5] mb-6 transition-colors duration-300 group-hover:bg-[#fbfbfb1f]">
            <div class="w-6 h-6 bg-black group-hover:bg-[#d4ff00] transition-colors duration-300" style="
                  mask-image: url('<?php echo get_template_directory_uri(); ?>/assets/images/icons/Improved\ Hygiene.png'); 
        -webkit-mask-image: url('<?php echo get_template_directory_uri(); ?>/assets/images/icons/Improved\ Hygiene.png'); 
        mask-repeat: no-repeat; 
        mask-size: contain; 
        -webkit-mask-size: contain; 
        mask-position: center;
        -webkit-mask-position: center;
                "></div>
          </div>

          <!-- TITLE -->
          <h3
            class="text-[24px] font-medium font-[Helvetica] tracking-wide text-[#15181F] mb-2 sm:mb-3 transition-colors duration-300 group-hover:text-white leading-tight">
           Improved Hygiene
          </h3>

          <!-- TEXT -->
          <p
            class="text-[#474C59] text-[18px] font-[Montserrat] font-regular leading-relaxed transition-colors duration-300 group-hover:text-white leading-tight">
           With no separate adhesive labels, the surface remains smooth and easy to clean, making it suitable for packaging applications.
          </p>
        </div>

      </div>
    </div>
  </section>
  <!-- ================= why chose us end ================= -->
   <section class="py-20 bg-[#F5F6FA] font-sans">
    <div class="reveal max-w-7xl mx-auto px-6">

      <div class="reveal flex flex-col lg:flex-row gap-12 items-start ">

        <div class="reveal w-full lg:w-3/5">
          <div class="reveal inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-[#D7FF80]/50 mb-4">
            <span class="w-2 h-2 rounded-full bg-[#153F45]"></span>
            <span class="reveal text-[16PX] font-medium text-[#19191A] font-[Poppins] tracking-widest">Industries</span>
          </div>

          <h2 class="reveal text-4xl md:text-[40PX] font-medium text-[#19191A] font-[Helvetica] tracking-widest mb-6">Common Applications</h2>

          <p class="reveal text-[16PX] font-medium text-[#767C8C] font-[Montserrat] max-w-xl leading-relaxed mb-4">
           In-Mold Labelling is widely used across industries that demand durable, high-quality product branding.
          </p>

          <h4 class="reveal text-[20PX] font-semibold text-[#19191A] font-[Montserrat] mb-6">Our equipment includes tools such as:</h4>

          <ul class="reveal space-y-4">
            <li class="flex items-center gap-3">
              <div class="reveal w-6 h-6 rounded-full bg-[#143E44] flex items-center justify-center flex-shrink-0">
                <svg class="w-3.5 h-3.5 text-[#C8FF00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                </svg>
              </div>
              <span class="reveal text-[18PX] font-regular text-[#474C59] font-[Montserrat]">Food and beverage packaging</span>
            </li>
            <li class="reveal flex items-center gap-3">
              <div class="reveal w-6 h-6 rounded-full bg-[#143E44] flex items-center justify-center flex-shrink-0">
                <svg class="w-3.5 h-3.5 text-[#C8FF00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                </svg>
              </div>
              <span class="reveal text-[18PX] font-regular text-[#474C59] font-[Montserrat]">Consumer products</span>
            </li>
            <li class="reveal flex items-center gap-3">
              <div class="reveal w-6 h-6 rounded-full bg-[#143E44] flex items-center justify-center flex-shrink-0">
                <svg class="w-3.5 h-3.5 text-[#C8FF00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                </svg>
              </div>
              <span class="reveal text-[18PX] font-regular text-[#474C59] font-[Montserrat]">Household containers</span>
            </li>
            <li class="reveal flex items-center gap-3">
              <div class="reveal w-6 h-6 rounded-full bg-[#143E44] flex items-center justify-center flex-shrink-0">
                <svg class="w-3.5 h-3.5 text-[#C8FF00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                </svg>
              </div>
              <span class="reveal text-[18PX] font-regular text-[#474C59] font-[Montserrat]">Industrial packaging</span>
            </li>
             <li class="reveal flex items-center gap-3">
              <div class="reveal w-6 h-6 rounded-full bg-[#143E44] flex items-center justify-center flex-shrink-0">
                <svg class="w-3.5 h-3.5 text-[#C8FF00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                </svg>
              </div>
              <span class="reveal text-[18PX] font-regular text-[#474C59] font-[Montserrat]">Cosmetic packaging</span>
            </li>
          </ul>
        </div>

        <div class="reveal w-full max-w-xl bg-[#142F32] rounded-[32px] p-8 shadow-2xl relative overflow-hidden">
          <div class="relative z-10">
            <div class="flex items-center gap-7 mb-4 ">
              <div
                class=" bg-[#C8FF00]/10 rounded-lg flex items-center justify-center border border-[#C8FF00]/20">
                <div class=" animate-pulse"> <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/Precision Measurement.png" alt=""></div>
              </div>
              <h3 class="reveal text-[22px] font-semibold text-[#EBEDF0] font-[Poppins]">Looking to integrate high-quality labelling directly into your plastic products?</h3>
            </div>
           
            <div class="space-y-6">
              <div class="border-t border-[#474C59] mt-2 ">
                <p class="reveal text-[18px] font-medium text-[#C8CCD9] font-[Montserrat] leading-relaxed mt-3 ">
                 Our team can support your project with reliable <span class="font-bold text-white">In-Mold Labelling solutions.</span>  Get in touch to discuss your requirements.
                </p>
              </div>
              <div class="pt-6 border-t border-[#474C59] ">
                <a href="<?php echo esc_url(home_url('/register')); ?>"
            class="reveal bg-[#FFFFFF] border-[#C8CCD9] border text-[#19191A] px-5  py-1 sm:py-3 rounded-full text-[18px] font-[Montserrat] font-medium text-base flex gap-1 w-[160px]">Get Started
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/arrow-up-right (1).png" alt="arrow-up-right" class="w-[20px]" /></a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>


  <!-- ================= faq section start ================= -->
  <section class="w-full bg-[#142F32] py-16 px-6 md:px-12 lg:px-24 font-[Poppins]">

    <div class="reveal max-w-4xl mx-auto text-center pb-4">
      <p class="reveal text-[#C8FF00] font-medium font-[Helvetica] text-[20px] tracking-widest   uppercase">FAQ
      </p>
      <h2 class="text-white text-[24px] md:text-[40px] font-medium font-[Helvetica] text-[20px] ">
        Frequently Asked <span class="text-[#C8FF00]">Questions</span>
      </h2>
    </div>

    <div id="faq-list" class="reveal max-w-7xl mx-auto space-y-4 mb-4">

      <div class="faq-item rounded-2xl bg-[#1a3d46] border border-white/5 overflow-hidden transition-all duration-300">
        <button class="w-full flex items-center justify-between px-8 py-3 text-left group">
          <div class="flex items-center gap-4 md:gap-6">
            <span
              class="num-badge flex-shrink-0 w-10 h-10 rounded-full bg-[#2a505a] flex items-center justify-center text-white text-sm font-bold font-[Poppins] text-[16px] border border-white/10 transition-colors shadow-[inset_0_2px_4px_rgba(255,255,255,0.1)]">
              01
            </span>
            <h3 class="text-white text-[16px] md:text-[26px] font-[Helvetica] leading-tight">
              What is In-Mold Labelling (IML)?</h3>
          </div>

          <div class="flex-shrink-0 ml-4">
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/arrow-down-right.png" alt="Toggle"
              class="faq-icon-img w-[20px] h-[20px] transition-transform duration-500 ease-in-out opacity-60 group-hover:opacity-100"
              style="transform: rotate(0deg);">
          </div>
        </button>

        <div class="answer hidden px-6 md:px-8 pb-4 md:pl-24 md:pr-24  transition-all duration-300">
          <p class="text-[#F0F1F5] text-sm md:text-[20px] font-medium font-[Montserrat] leading-relaxed max-w-6xl">
            IML is a process where a printed label is placed inside the mould and fused to the plastic part during moulding.
          </p>
        </div>
      </div>

    </div>
    <div id="faq-list" class="reveal max-w-7xl mx-auto space-y-4 mb-4">

      <div class="faq-item rounded-2xl bg-[#1a3d46] border border-white/5 overflow-hidden transition-all duration-300">
        <button class="w-full flex items-center justify-between px-8 py-3 text-left group">
          <div class="flex items-center gap-4 md:gap-6">
            <span
              class="num-badge flex-shrink-0 w-10 h-10 rounded-full bg-[#2a505a] flex items-center justify-center text-white text-sm font-bold font-[Poppins] text-[16px] border border-white/10 transition-colors shadow-[inset_0_2px_4px_rgba(255,255,255,0.1)]">
              02
            </span>

            <h3 class="text-white text-[16px] md:text-[26px] font-[Helvetica] leading-tight">
              What are the advantages of IML over traditional labelling?
            </h3>
          </div>

          <div class="flex-shrink-0 ml-4">
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/arrow-down-right.png" alt="Toggle"
              class="faq-icon-img w-[20px] h-[20px] transition-transform duration-500 ease-in-out opacity-60 group-hover:opacity-100"
              style="transform: rotate(0deg);">
          </div>
        </button>

        <div class="answer hidden px-6 md:px-8 pb-4 md:pl-24 md:pr-24  transition-all duration-300">
          <p class="text-[#F0F1F5] text-sm md:text-[20px] font-medium font-[Montserrat] leading-relaxed max-w-6xl">
            IML offers a durable finish, better appearance, faster production, and no need for post-label application.
          </p>
        </div>
      </div>

    </div>
    <div id="faq-list" class="reveal max-w-7xl mx-auto space-y-4 mb-4">

      <div class="faq-item rounded-2xl bg-[#1a3d46] border border-white/5 overflow-hidden transition-all duration-300">
        <button class="w-full flex items-center justify-between px-8 py-3 text-left group">
          <div class="flex items-center gap-4 md:gap-6">
            <span
              class="num-badge flex-shrink-0 w-10 h-10 rounded-full bg-[#2a505a] flex items-center justify-center text-white text-sm font-bold font-[Poppins] text-[16px] border border-white/10 transition-colors shadow-[inset_0_2px_4px_rgba(255,255,255,0.1)]">
              03
            </span>
            <h3 class="text-white text-[16px] md:text-[26px] font-[Helvetica] leading-tight">
              What industries commonly use IML?
            </h3>
          </div>

          <div class="flex-shrink-0 ml-4">
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/arrow-down-right.png" alt="Toggle"
              class="faq-icon-img w-[20px] h-[20px] transition-transform duration-500 ease-in-out opacity-60 group-hover:opacity-100"
              style="transform: rotate(0deg);">
          </div>
        </button>

        <div class="answer hidden px-6 md:px-8 pb-4 md:pl-24 md:pr-24  transition-all duration-300">
          <p class="text-[#F0F1F5] text-sm md:text-[20px] font-medium font-[Montserrat] leading-relaxed max-w-6xl">
            IML is widely used in packaging, food tubs, cosmetics, household products, and consumer goods.
          </p>
        </div>
      </div>

    </div>
    <div id="faq-list" class="reveal max-w-7xl mx-auto space-y-4 mb-4">

      <div class="faq-item rounded-2xl bg-[#1a3d46] border border-white/5 overflow-hidden transition-all duration-300">
        <button class="w-full flex items-center justify-between px-8 py-3 text-left group">
          <div class="flex items-center gap-4 md:gap-6">
            <span
              class="num-badge flex-shrink-0 w-10 h-10 rounded-full bg-[#2a505a] flex items-center justify-center text-white text-sm font-bold font-[Poppins] text-[16px] border border-white/10 transition-colors shadow-[inset_0_2px_4px_rgba(255,255,255,0.1)]">
              04
            </span>
            <h3 class="text-white text-[16px] md:text-[26px] font-[Helvetica] leading-tight">
              Can IML be used with different plastic materials?
            </h3>
          </div>

          <div class="flex-shrink-0 ml-4">
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/arrow-down-right.png" alt="Toggle"
              class="faq-icon-img w-[20px] h-[20px] transition-transform duration-500 ease-in-out opacity-60 group-hover:opacity-100"
              style="transform: rotate(0deg);">
          </div>
        </button>

        <div class="answer hidden px-6 md:px-8 pb-4 md:pl-24 md:pr-24  transition-all duration-300">
          <p class="text-[#F0F1F5] text-sm md:text-[20px] font-medium font-[Montserrat] leading-relaxed max-w-6xl">
            Yes, IML is commonly used with materials such as PP and other compatible plastics.
          </p>
        </div>
      </div>

    </div>
    <div id="faq-list" class="reveal max-w-7xl mx-auto space-y-4 mb-4">

      <div class="faq-item rounded-2xl bg-[#1a3d46] border border-white/5 overflow-hidden transition-all duration-300">
        <button class="w-full flex items-center justify-between px-8 py-3 text-left group">
          <div class="flex items-center gap-4 md:gap-6">
            <span
              class="num-badge flex-shrink-0 w-10 h-10 rounded-full bg-[#2a505a] flex items-center justify-center text-white text-sm font-bold font-[Poppins] text-[16px] border border-white/10 transition-colors shadow-[inset_0_2px_4px_rgba(255,255,255,0.1)]">
              05
            </span>
            <h3 class="text-white text-[16px] md:text-[26px] font-[Helvetica] leading-tight">
              Does IML affect production speed?
            </h3>
          </div>

          <div class="flex-shrink-0 ml-4">
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/arrow-down-right.png" alt="Toggle"
              class="faq-icon-img w-[20px] h-[20px] transition-transform duration-500 ease-in-out opacity-60 group-hover:opacity-100"
              style="transform: rotate(0deg);">
          </div>
        </button>

        <div class="answer hidden px-6 md:px-8 pb-4 md:pl-24 md:pr-24  transition-all duration-300">
          <p class="text-[#F0F1F5] text-sm md:text-[20px] font-medium font-[Montserrat] leading-relaxed max-w-6xl">
            IML can be highly efficient in volume production by combining moulding and labelling in one process.
          </p>
        </div>
      </div>

    </div>


  </section>

  <!-- ================= FAQ section end ================= -->













</main>









<?php get_footer(); ?>



